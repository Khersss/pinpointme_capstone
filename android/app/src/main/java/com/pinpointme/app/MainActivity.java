package com.pinpointme.app;

import android.Manifest;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.media.AudioAttributes;
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

        // Create notification channels for Android 8+ (required for notifications to display)
        createNotificationChannels();

        // Register file chooser result handler
        fileChooserLauncher = registerForActivityResult(
            new ActivityResultContracts.StartActivityForResult(),
            result -> {
                if (filePathCallback == null) return;

                Uri[] results = null;
                if (result.getResultCode() == RESULT_OK && result.getData() != null) {
                    // Check for multiple files via ClipData first
                    if (result.getData().getClipData() != null) {
                        int count = result.getData().getClipData().getItemCount();
                        results = new Uri[count];
                        for (int i = 0; i < count; i++) {
                            results[i] = result.getData().getClipData().getItemAt(i).getUri();
                        }
                    } else if (result.getData().getDataString() != null) {
                        // Single file selection
                        results = new Uri[]{Uri.parse(result.getData().getDataString())};
                    }
                }
                
                // If no result from file picker, check camera capture
                if (results == null && result.getResultCode() == RESULT_OK && cameraPhotoPath != null) {
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
                boolean acceptsAll = acceptTypes == null || acceptTypes.length == 0 || (acceptTypes.length == 1 && acceptTypes[0].isEmpty()) || acceptsType(acceptTypes, "*/*");

                if (acceptsImages || acceptsAll) {
                    // Camera capture intent
                    Intent takePictureIntent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
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
                    
                    // Gallery picker intent (explicit for better Android 11+ support)
                    Intent galleryIntent = new Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI);
                    galleryIntent.setType("image/*");
                    intentList.add(galleryIntent);
                }

                if (acceptsVideo || acceptsAll) {
                    Intent takeVideoIntent = new Intent(MediaStore.ACTION_VIDEO_CAPTURE);
                    intentList.add(takeVideoIntent);
                }

                // If capture is explicitly requested, use camera directly
                if (captureEnabled && !intentList.isEmpty()) {
                    fileChooserLauncher.launch(intentList.get(0));
                    return true;
                }

                // File picker intent (ACTION_GET_CONTENT for broad file access)
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

                // Also add ACTION_OPEN_DOCUMENT intent for document browser access
                Intent openDocumentIntent = new Intent(Intent.ACTION_OPEN_DOCUMENT);
                openDocumentIntent.addCategory(Intent.CATEGORY_OPENABLE);
                openDocumentIntent.setType("*/*");
                openDocumentIntent.putExtra(Intent.EXTRA_MIME_TYPES, new String[]{
                    "application/pdf",
                    "application/msword",
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/vnd.ms-excel",
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/vnd.ms-powerpoint",
                    "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                    "text/plain",
                    "application/zip",
                    "image/*",
                    "video/*",
                    "audio/*"
                });
                intentList.add(openDocumentIntent);

                // Create chooser with camera + document browser intents
                Intent chooserIntent = Intent.createChooser(contentSelectionIntent, "Select File");
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

    /**
     * Create notification channels required for Android 8+ (API 26).
     * Without these channels, FCM notifications will NOT display when the app is closed/killed.
     * The channelId must match what's used in the FCM message payload (functions/index.js).
     */
    private void createNotificationChannels() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationManager notificationManager = getSystemService(NotificationManager.class);

            // Primary channel for rescue/emergency alerts (matches channelId in functions/index.js)
            NotificationChannel rescueChannel = new NotificationChannel(
                "rescue_alerts",
                "Rescue Alerts",
                NotificationManager.IMPORTANCE_HIGH
            );
            rescueChannel.setDescription("Emergency rescue and alert notifications");
            rescueChannel.enableVibration(true);
            rescueChannel.setVibrationPattern(new long[]{0, 500, 200, 500});
            rescueChannel.enableLights(true);
            rescueChannel.setShowBadge(true);
            rescueChannel.setLockscreenVisibility(android.app.Notification.VISIBILITY_PUBLIC);
            AudioAttributes audioAttributes = new AudioAttributes.Builder()
                .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                .setUsage(AudioAttributes.USAGE_NOTIFICATION)
                .build();
            rescueChannel.setSound(android.provider.Settings.System.DEFAULT_NOTIFICATION_URI, audioAttributes);
            notificationManager.createNotificationChannel(rescueChannel);

            // Default channel for general notifications
            NotificationChannel defaultChannel = new NotificationChannel(
                "default",
                "General Notifications",
                NotificationManager.IMPORTANCE_DEFAULT
            );
            defaultChannel.setDescription("General app notifications");
            defaultChannel.enableVibration(true);
            defaultChannel.setShowBadge(true);
            notificationManager.createNotificationChannel(defaultChannel);

            Log.d(TAG, "Notification channels created: rescue_alerts, default");
        }
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