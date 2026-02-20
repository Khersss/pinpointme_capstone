<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    protected $projectId;
    protected $functionsUrl;
    
    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', 'pinpointme-app');
        $this->functionsUrl = "https://us-central1-{$this->projectId}.cloudfunctions.net";
    }
    
    /**
     * Send push notifications to users via Firebase Cloud Functions
     * This will send to both web and native FCM tokens stored in Firestore
     * 
     * @param string $title
     * @param string $body
     * @param array $userIds
     * @param array $data
     * @return array
     */
    public function sendNotifications($title, $body, $userIds, $data = [])
    {
        Log::info('Firebase: Sending notifications via Cloud Functions', [
            'title' => $title,
            'body' => $body,
            'user_ids' => $userIds,
            'data_keys' => array_keys($data),
        ]);

        try {
            $response = Http::timeout(30)->post("{$this->functionsUrl}/sendNotifications", [
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'userIds' => $userIds,
                    'data' => $data
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Firebase: Notifications sent successfully', [
                    'result' => $result
                ]);
                return $result;
            } else {
                Log::error('Firebase: Failed to send notifications', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: {$response->body()}"
                ];
            }
        } catch (\Exception $e) {
            Log::error('Firebase: Exception sending notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send message notification to conversation participants
     * 
     * @param array $userIds
     * @param string $senderName
     * @param string $messageContent
     * @param int $conversationId
     * @param int $messageId
     * @param int $senderId
     * @return array
     */
    public function sendMessageNotification($userIds, $senderName, $messageContent, $conversationId, $messageId, $senderId)
    {
        $title = "💬 New message from {$senderName}";
        $body = strlen($messageContent) > 50 ? substr($messageContent, 0, 50) . '...' : $messageContent;
        
        $data = [
            'type' => 'message',
            'conversation_id' => (string)$conversationId,
            'message_id' => (string)$messageId,
            'sender_id' => (string)$senderId,
            'sender_name' => $senderName,
            'click_action' => "/user/chat/{$conversationId}",
            'timestamp' => now()->toIso8601String(),
        ];

        return $this->sendNotifications($title, $body, $userIds, $data);
    }
}