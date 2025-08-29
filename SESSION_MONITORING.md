# Session Monitoring Implementation

## Overview

This implementation provides automatic session monitoring and logout functionality for the NPC Technician app. When a user's session expires, they are automatically logged out and redirected to the login screen.

## Features

### 1. Automatic Session Monitoring
- **Continuous Monitoring**: The app continuously monitors session expiry every 30 seconds
- **Background/Foreground Handling**: Session monitoring adapts to app lifecycle changes
- **Early Warning**: Users receive warnings 5 minutes before session expiry
- **Automatic Logout**: Users are automatically logged out when session expires

### 2. Session Expiry Detection
- **Real-time Checking**: Session validity is checked against stored expiry time
- **Buffer Time**: 1-minute buffer to prevent edge cases
- **Error Handling**: Graceful handling of parsing errors and invalid data

### 3. User Experience
- **Warning Notifications**: Users see warnings before session expiry
- **Refresh Option**: Users can refresh their session from the warning
- **Smooth Navigation**: Automatic redirection to login screen
- **Clear Messaging**: Informative messages about session status

## Implementation Details

### Core Components

#### 1. SessionMonitorService (`lib/services/session_monitor_service.dart`)
- **Singleton Pattern**: Ensures single instance across the app
- **Timer-based Monitoring**: Checks session every 30 seconds
- **Context Management**: Handles navigation and UI updates
- **Testing Support**: Includes methods for testing session expiry

#### 2. Enhanced AuthService (`lib/services/auth_service.dart`)
- **Improved Session Checking**: Better expiry validation with buffer time
- **Thorough Cleanup**: Comprehensive session data removal
- **Error Handling**: Robust error handling for session operations

#### 3. Updated Main App (`lib/main.dart`)
- **Session Monitoring Integration**: Starts monitoring when valid session exists
- **Lifecycle Management**: Proper cleanup on app disposal

#### 4. Enhanced Dashboard (`lib/screens/technician_dashboard.dart`)
- **Lifecycle Observer**: Handles app background/foreground transitions
- **Session Validation**: Checks session on app resume
- **Proper Cleanup**: Stops monitoring on logout

### Key Methods

#### SessionMonitorService
```dart
// Start monitoring session expiry
void startMonitoring(BuildContext context)

// Stop monitoring
void stopMonitoring()

// Check session expiry
Future<void> _checkSessionExpiry(BuildContext context)

// Handle expired session
Future<void> _handleSessionExpired(BuildContext context)

// Show expiry warning
void _showSessionExpiryWarning(BuildContext context, Duration timeUntilExpiry)

// Refresh session
Future<void> _refreshSession(BuildContext context)
```

#### AuthService
```dart
// Check if user is logged in with expiry validation
Future<bool> isLoggedIn()

// Logout and clear session
Future<void> logout()

// Clear all session data
Future<void> _clearSession()
```

## Usage

### Automatic Behavior
1. **Login**: When user logs in, session monitoring starts automatically
2. **Background**: Monitoring continues while app is in background
3. **Foreground**: Session is validated when app comes to foreground
4. **Expiry**: Automatic logout and redirect to login screen

### Manual Testing
For development/testing purposes, a test button is available in debug mode:

```dart
// Test button appears only in debug builds
if (const bool.fromEnvironment('dart.vm.product') == false)
  ElevatedButton(
    onPressed: () async {
      // Set session to expire in 10 seconds
      final expiryTime = DateTime.now().add(Duration(seconds: 10));
      await _sessionMonitor.setSessionExpiryForTesting(expiryTime);
    },
    child: Text('Test Session Expiry (10s)'),
  )
```

## Configuration

### Monitoring Interval
Session is checked every 30 seconds:
```dart
_sessionCheckTimer = Timer.periodic(const Duration(seconds: 30), (timer) {
  _checkSessionExpiry(context);
});
```

### Warning Threshold
Warnings are shown 5 minutes before expiry:
```dart
if (timeUntilExpiry.inMinutes <= 5) {
  _showSessionExpiryWarning(context, timeUntilExpiry);
}
```

### Buffer Time
1-minute buffer to prevent edge cases:
```dart
if (now.isAfter(expiryDateTime.subtract(const Duration(minutes: 1)))) {
  await _clearSession();
  return false;
}
```

## Error Handling

### Session Data Issues
- Invalid expiry time format
- Missing session data
- Corrupted preferences

### Network Issues
- Failed logout API calls
- Network connectivity problems

### App Lifecycle
- App backgrounding/foregrounding
- Widget disposal
- Context availability

## Security Considerations

### Session Data
- All session data is cleared on logout
- No sensitive data remains in memory
- Secure session ID handling

### API Calls
- Proper headers for logout requests
- Error handling for failed requests
- Graceful degradation

## Testing

### Manual Testing
1. Login to the app
2. Use the test button to set session expiry to 10 seconds
3. Wait for automatic logout
4. Verify redirect to login screen

### Automated Testing
```dart
// Test session expiry
await sessionMonitor.setSessionExpiryForTesting(
  DateTime.now().add(Duration(seconds: 5))
);

// Wait for expiry
await Future.delayed(Duration(seconds: 6));

// Verify logout occurred
expect(await authService.isLoggedIn(), false);
```

## Troubleshooting

### Common Issues

1. **Session not expiring**
   - Check session expiry time format
   - Verify monitoring is active
   - Check for timezone issues

2. **Multiple logout dialogs**
   - Ensure single instance of SessionMonitorService
   - Check for duplicate monitoring starts

3. **Navigation issues**
   - Verify context is mounted
   - Check route availability
   - Ensure proper navigation stack

### Debug Information
Enable debug logging to see session monitoring activity:
```dart
print('Session monitoring started');
print('Session expired at $expiryDateTime, current time: $now');
print('All session data cleared');
```

## Future Enhancements

### Potential Improvements
1. **Configurable Monitoring**: Allow users to adjust monitoring frequency
2. **Session Refresh**: Automatic session refresh before expiry
3. **Offline Support**: Handle session expiry when offline
4. **Biometric Authentication**: Add biometric re-authentication
5. **Session Analytics**: Track session usage patterns

### Performance Optimizations
1. **Battery Optimization**: Reduce monitoring frequency in background
2. **Memory Management**: Optimize timer usage
3. **Network Efficiency**: Batch session checks
