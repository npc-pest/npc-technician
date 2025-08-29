import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../utils/constants.dart';
import '../models/technician.dart';

class AuthService {
  static final AuthService _instance = AuthService._internal();
  factory AuthService() => _instance;
  AuthService._internal();

  String? _sessionId;
  Technician? _currentTechnician;

  String? get sessionId => _sessionId;
  Technician? get currentTechnician => _currentTechnician;

  Future<void> initialize() async {
    await _loadSession();
  }

  Future<void> _loadSession() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    _sessionId = prefs.getString(AppConstants.sessionIdKey);

    String? technicianData = prefs.getString('technician_data');
    if (technicianData != null) {
      try {
        Map<String, dynamic> data = json.decode(technicianData);
        _currentTechnician = Technician.fromJson(data);
      } catch (e) {
        print('Error loading technician data: $e');
      }
    }
  }

  Future<bool> loginWithOTP(String phone, String otp) async {
    try {
      final response = await http.post(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.otpLoginEndpoint}'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'phone': phone,
          'otp': otp,
          'role': 'Technician',
        }),
      );

      print('OTP Login Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['status'] == 'success') {
          await _saveSession(data);
          return true;
        } else {
          throw Exception(data['message'] ?? 'Login failed');
        }
      } else {
        throw Exception('Network error: ${response.statusCode}');
      }
    } catch (e) {
      print('OTP Login error: $e');
      rethrow;
    }
  }

  Future<bool> loginWithPassword(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.loginEndpoint}'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'email': email,
          'password': password,
          'fcm_token': 'mobile_token', // For mobile app
        }),
      );

      print('Password Login Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['status'] == 'success') {
          await _saveSession(data);
          return true;
        } else {
          throw Exception(data['message'] ?? 'Login failed');
        }
      } else {
        throw Exception('Network error: ${response.statusCode}');
      }
    } catch (e) {
      print('Password Login error: $e');
      rethrow;
    }
  }

  Future<void> _saveSession(Map<String, dynamic> data) async {
    SharedPreferences prefs = await SharedPreferences.getInstance();

    _sessionId = data['sessionid'] ?? data['session_id'];
    print('Debug: Saving session ID: $_sessionId');

    // Create technician object from available data
    Map<String, dynamic> technicianData = {
      'technician_id': data['data']?['technician_id'] ?? 0,
      'employee_name': data['data']?['employee_name'] ?? 'Technician',
      'phone_number': data['data']?['phone_number'] ?? '',
      'email': data['data']?['email'] ?? '',
    };

    _currentTechnician = Technician.fromJson(technicianData);

    await prefs.setString(AppConstants.sessionIdKey, _sessionId!);
    await prefs.setString(AppConstants.roleKey, 'Technician');
    await prefs.setString(
        AppConstants.sessionExpiryKey, data['session_expiry'] ?? '');
    await prefs.setString('technician_data', json.encode(technicianData));

    print('Debug: Session saved to preferences. Session ID: $_sessionId');

    if (data['fcm_token'] != null) {
      await prefs.setString(AppConstants.fcmTokenKey, data['fcm_token']);
    }
  }

  Future<void> logout() async {
    try {
      if (_sessionId != null) {
        // Call logout API if needed
        await http.post(
          Uri.parse('${AppConstants.baseUrl}logout.php'),
          headers: {
            'Technician-ID': _sessionId!,
            'Content-Type': 'application/json',
          },
        );
      }
    } catch (e) {
      print('Logout API error: $e');
    } finally {
      await _clearSession();
      print('Session cleared successfully');
    }
  }

  Future<void> _clearSession() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();

    // Clear all session-related data
    await prefs.remove(AppConstants.sessionIdKey);
    await prefs.remove(AppConstants.roleKey);
    await prefs.remove(AppConstants.sessionExpiryKey);
    await prefs.remove(AppConstants.technicianIdKey);
    await prefs.remove(AppConstants.technicianNameKey);
    await prefs.remove(AppConstants.technicianPhoneKey);
    await prefs.remove(AppConstants.fcmTokenKey);
    await prefs.remove('technician_data');

    _sessionId = null;
    _currentTechnician = null;

    print('All session data cleared');
  }

  Future<bool> isLoggedIn() async {
    if (_sessionId == null) {
      await _loadSession();
    }

    if (_sessionId == null) return false;

    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? sessionExpiry = prefs.getString(AppConstants.sessionExpiryKey);

    if (sessionExpiry == null) {
      await _clearSession();
      return false;
    }

    try {
      DateTime expiryDateTime = DateTime.parse(sessionExpiry);
      DateTime now = DateTime.now();

      // Add a small buffer (1 minute) to prevent edge cases
      if (now.isAfter(expiryDateTime.subtract(const Duration(minutes: 1)))) {
        print('Session expired at $expiryDateTime, current time: $now');
        await _clearSession();
        return false;
      }

      return true;
    } catch (e) {
      print('Error checking session expiry: $e');
      await _clearSession();
      return false;
    }
  }

  Future<void> refreshSession() async {
    if (_sessionId == null) return;

    try {
      final response = await http.post(
        Uri.parse('${AppConstants.baseUrl}refresh_session.php'),
        headers: {
          'Technician-ID': _sessionId!,
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          await _saveSession(data);
        }
      }
    } catch (e) {
      print('Error refreshing session: $e');
    }
  }

  Future<bool> sendOTP(String phone) async {
    try {
      final response = await http.post(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.sendOtpEndpoint}'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'phone': phone,
          'role': 'Technician',
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['status'] == 'success';
      }

      return false;
    } catch (e) {
      print('Send OTP error: $e');
      return false;
    }
  }
}
