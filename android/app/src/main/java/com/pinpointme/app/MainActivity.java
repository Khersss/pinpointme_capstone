package com.pinpointme.app;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.provider.MediaStore;
import android.util.Log;
import android.webkit.PermissionRequest;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;

import com.getcapacitor.BridgeActivity;

import java.io.File;
import java.io.IOException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Locale;

public class MainActivity extends BridgeActivity {

    private static final String TAG = "MainActivity";
    private static final int PERMISSION_REQUEST_CODE = 100;

    private ValueCallback<Uri[]> filePathCallback;
    private String cameraPhotoPath;

    private ActivityResultLauncher<Intent> fileChooserLauncher;

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Register file chooser result handler
        fileChooserLauncher = registerForActivityResult(
            new ActivityResultContracts.StartActivityForResult(),
            result -> {
                if (filePathCallback == null) return;

                Uri[] results = null;
                if (result.getResultCode() == RESULT_OK && result.getData() != null) {
                    String dataString = result.getData().getDataString();
                    if (dataString != null) {
                        results = new Uri[]{Uri.parse(dataString)};
                    }
                } else if (result.getResultCode() == RESULT_OK && cameraPhotoPath != null) {
                    // Camera capture result
                    results = new Uri[]{Uri.parse(cameraPhotoPath)};
                }

                filePathCallback.onReceiveValue(results);
                filePathCallback = null;
            }
        );

        // Request runtime permissions for camera, mic, notifications
        requestAppPermissions();

        // Set up WebChromeClient for camera/mic permissions and file uploads
        WebView webView = (WebView) bridge.getWebView();
        webView.getSettings().setMediaPlaybackRequiresUserGesture(false);
        webView.getSettings().setAllowFileAccess(true);
        webView.getSettings().setAllowContentAccess(true);

        webView.setWebChromeClient(new WebChromeClient() {

            // Handle camera/mic permission requests from the web page
            @Override
            public void onPermissionRequest(final PermissionRequest request) {
                Log.d(TAG, "onPermissionRequest: " + java.util.Arrays.toString(request.getResources()));
                runOnUiThread(() -> request.grant(request.getResources()));
            }

            // Handle file upload / camera capture from <input type="file">
            @Override
            public boolean onShowFileChooser(
                WebView webView,
                ValueCallback<Uri[]> uploadCallback,
                FileChooserParams fileChooserParams
            ) {
                Log.d(TAG, "onShowFileChooser triggered");

                // Cancel any existing callback
                if (filePathCallback != null) {
                    filePathCallback.onReceiveValue(null);
                }
                filePathCallback = uploadCallback;

                String[] acceptTypes = fileChooserParams.getAcceptTypes();
                boolean captureEnabled = fileChooserParams.isCaptureEnabled();

                // Build intents
                List<Intent> intentList = new ArrayList<>();

                // Camera intent for image capture
                boolean acceptsImages = acceptsType(acceptTypes, "image");
                boolean acceptsVideo = acceptsType(acceptTypes, "video");

                if (acceptsImages || acceptTypes == null || acceptTypes.length == 0 || (acceptTypes.length == 1 && acceptTypes[0].isEmpty())) {
                    Intent takePictureIntent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
                    if (takePictureIntent.resolveActivity(getPackageManager()) != null) {
                        File photoFile = null;
                        try {
                            photoFile = createImageFile();
                        } catch (IOException ex) {
                            Log.e(TAG, "Error creating image file", ex);
                        }
                        if (photoFile != null) {
                            cameraPhotoPath = "file:" + photoFile.getAbsolutePath();
                            Uri photoURI = FileProvider.getUriForFile(
                                MainActivity.this,
                                getApplicationContext().getPackageName() + ".fileprovider",
                                photoFile
                            );
                            takePictureIntent.putExtra(MediaStore.EXTRA_OUTPUT, photoURI);
                            intentList.add(takePictureIntent);
                        }
                    }
                }

                if (acceptsVideo) {
                    Intent takeVideoIntent = new Intent(MediaStore.ACTION_VIDEO_CAPTURE);
                    if (takeVideoIntent.resolveActivity(getPackageManager()) != null) {
                        intentList.add(takeVideoIntent);
                    }
                }

                // If capture is explicitly requested, use camera directly
                if (captureEnabled && !intentList.isEmpty()) {
                    fileChooserLauncher.launch(intentList.get(0));
                    return true;
                }

                // File picker intent
                Intent contentSelectionIntent = new Intent(Intent.ACTION_GET_CONTENT);
                contentSelectionIntent.addCategory(Intent.CATEGORY_OPENABLE);

                if (acceptTypes != null && acceptTypes.length > 0 && !acceptTypes[0].isEmpty()) {
                    contentSelectionIntent.setType(acceptTypes[0]);
                    if (acceptTypes.length > 1) {
                        contentSelectionIntent.putExtra(Intent.EXTRA_MIME_TYPES, acceptTypes);
                    }
                } else {
                    contentSelectionIntent.setType("*/*");
                }

                // Create chooser with camera intents
                Intent chooserIntent = Intent.createChooser(contentSelectionIntent, "Select");
                if (!intentList.isEmpty()) {
                    chooserIntent.putExtra(
                        Intent.EXTRA_INITIAL_INTENTS,
                        intentList.toArray(new Intent[0])
                    );
                }

                fileChooserLauncher.launch(chooserIntent);
                return true;
            }
        });
    }

    private boolean acceptsType(String[] types, String typePrefix) {
        if (types == null || types.length == 0) return true;
        for (String type : types) {
            if (type != null && (type.startsWith(typePrefix) || type.equals("*/*"))) {
                return true;
            }
        }
        return false;
    }

    private File createImageFile() throws IOException {
        String timeStamp = new SimpleDateFormat("yyyyMMdd_HHmmss", Locale.US).format(new Date());
        String imageFileName = "JPEG_" + timeStamp + "_";
        File storageDir = getExternalFilesDir(Environment.DIRECTORY_PICTURES);
        return File.createTempFile(imageFileName, ".jpg", storageDir);
    }

    private void requestAppPermissions() {
        List<String> permissionsNeeded = new ArrayList<>();

        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            permissionsNeeded.add(Manifest.permission.CAMERA);
        }
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.RECORD_AUDIO) != PackageManager.PERMISSION_GRANTED) {
            permissionsNeeded.add(Manifest.permission.RECORD_AUDIO);
        }

        // Notification permission (Android 13+)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                permissionsNeeded.add(Manifest.permission.POST_NOTIFICATIONS);
            }
        }

        // Storage permissions (Android 12 and below)
        if (Build.VERSION.SDK_INT <= Build.VERSION_CODES.S_V2) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.READ_EXTERNAL_STORAGE) != PackageManager.PERMISSION_GRANTED) {
                permissionsNeeded.add(Manifest.permission.READ_EXTERNAL_STORAGE);
            }
        }

        // Media permissions (Android 13+)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.READ_MEDIA_IMAGES) != PackageManager.PERMISSION_GRANTED) {
                permissionsNeeded.add(Manifest.permission.READ_MEDIA_IMAGES);
            }
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.READ_MEDIA_VIDEO) != PackageManager.PERMISSION_GRANTED) {
                permissionsNeeded.add(Manifest.permission.READ_MEDIA_VIDEO);
            }
        }

        if (!permissionsNeeded.isEmpty()) {
            ActivityCompat.requestPermissions(this, permissionsNeeded.toArray(new String[0]), PERMISSION_REQUEST_CODE);
        }
    }
}