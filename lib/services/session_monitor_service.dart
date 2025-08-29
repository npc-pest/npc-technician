import 'dart:async';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';
import 'auth_service.dart';

class SessionMonitorService {
  static final SessionMonitorService _instance =
      SessionMonitorService._internal();
  factory SessionMonitorService() => _instance;
  SessionMonitorService._internal();

  Timer? _sessionCheckTimer;
  final AuthService _authService = AuthService();
  bool _isMonitoring = false;

  /// Start monitoring the session for expiry
  void startMonitoring(BuildContext context) {
    if (_isMonitoring) return;

    _isMonitoring = true;

    // Check session every 30 seconds
    _sessionCheckTimer = Timer.periodic(const Duration(seconds: 30), (timer) {
      _checkSessionExpiry(context);
    });

    print('Session monitoring started');
  }

  /// Stop monitoring the session
  void stopMonitoring() {
    _sessionCheckTimer?.cancel();
    _sessionCheckTimer = null;
    _isMonitoring = false;
    print('Session monitoring stopped');
  }

  /// Check if the current session has expired
  Future<void> _checkSessionExpiry(BuildContext context) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? sessionExpiry = prefs.getString(AppConstants.sessionExpiryKey);

      if (sessionExpiry == null) {
        await _handleSessionExpired(context);
        return;
      }

      DateTime expiryDateTime = DateTime.parse(sessionExpiry);
      DateTime now = DateTime.now();

      // Check if session has expired
      if (now.isAfter(expiryDateTime)) {
        await _handleSessionExpired(context);
        return;
      }

      // Check if session will expire in the next 5 minutes
      Duration timeUntilExpiry = expiryDateTime.difference(now);
      if (timeUntilExpiry.inMinutes <= 5) {
        // Show warning to user
        _showSessionExpiryWarning(context, timeUntilExpiry);
      }
    } catch (e) {
      print('Error checking session expiry: $e');
      await _handleSessionExpired(context);
    }
  }

  /// Handle session expired by logging out and navigating to login
  Future<void> _handleSessionExpired(BuildContext context) async {
    print('Session expired, logging out user');

    // Stop monitoring
    stopMonitoring();

    // Clear session
    await _authService.logout();

    // Show session expired message
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Session expired. Please login again.'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 3),
        ),
      );

      // Navigate to login screen
      Navigator.of(context).pushNamedAndRemoveUntil(
        '/password-login',
        (route) => false,
      );
    }
  }

  /// Show warning when session is about to expire
  void _showSessionExpiryWarning(
      BuildContext context, Duration timeUntilExpiry) {
    if (!context.mounted) return;

    final minutes = timeUntilExpiry.inMinutes;
    final seconds = timeUntilExpiry.inSeconds % 60;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          'Session will expire in ${minutes}m ${seconds}s. Please save your work.',
        ),
        backgroundColor: Colors.orange,
        duration: const Duration(seconds: 5),
        action: SnackBarAction(
          label: 'Refresh',
          textColor: Colors.white,
          onPressed: () => _refreshSession(context),
        ),
      ),
    );
  }

  /// Refresh the session
  Future<void> _refreshSession(BuildContext context) async {
    try {
      await _authService.refreshSession();

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Session refreshed successfully'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );
      }
    } catch (e) {
      print('Error refreshing session: $e');
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Failed to refresh session'),
            backgroundColor: Colors.red,
            duration: Duration(seconds: 2),
          ),
        );
      }
    }
  }

  /// Check if monitoring is active
  bool get isMonitoring => _isMonitoring;

  /// Manually trigger session expiry (for testing purposes)
  Future<void> triggerSessionExpiry(BuildContext context) async {
    print('Manually triggering session expiry for testing');
    await _handleSessionExpired(context);
  }

  /// Set session expiry to a specific time (for testing purposes)
  Future<void> setSessionExpiryForTesting(DateTime expiryTime) async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setString(
        AppConstants.sessionExpiryKey, expiryTime.toIso8601String());
    print('Session expiry set to $expiryTime for testing');
  }
}
