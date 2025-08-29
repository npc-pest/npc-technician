import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class UserDataManager {
  static final UserDataManager _instance = UserDataManager._internal();
  factory UserDataManager() => _instance;
  UserDataManager._internal();

  SharedPreferences? _prefs;

  Future<void> initialize() async {
    _prefs = await SharedPreferences.getInstance();
  }

  Future<void> saveSessionData({
    required String sessionId,
    required String role,
    required String sessionExpiry,
    Map<String, dynamic>? userData,
  }) async {
    if (_prefs == null) await initialize();

    await _prefs!.setString(AppConstants.sessionIdKey, sessionId);
    await _prefs!.setString(AppConstants.roleKey, role);
    await _prefs!.setString(AppConstants.sessionExpiryKey, sessionExpiry);

    if (userData != null) {
      await _prefs!.setString('user_data', userData.toString());
    }
  }

  Future<void> clearSessionData() async {
    if (_prefs == null) await initialize();
    await _prefs!.clear();
  }

  String? getSessionId() {
    return _prefs?.getString(AppConstants.sessionIdKey);
  }

  String? getRole() {
    return _prefs?.getString(AppConstants.roleKey);
  }

  String? getSessionExpiry() {
    return _prefs?.getString(AppConstants.sessionExpiryKey);
  }

  Map<String, dynamic>? getUserData() {
    final userDataString = _prefs?.getString('user_data');
    if (userDataString != null) {
      try {
        // Parse the string back to Map
        // This is a simple implementation - you might want to use JSON
        return {'data': userDataString};
      } catch (e) {
        print('Error parsing user data: $e');
        return null;
      }
    }
    return null;
  }

  Future<void> saveFCMToken(String token) async {
    if (_prefs == null) await initialize();
    await _prefs!.setString(AppConstants.fcmTokenKey, token);
  }

  String? getFCMToken() {
    return _prefs?.getString(AppConstants.fcmTokenKey);
  }

  bool isLoggedIn() {
    final sessionId = getSessionId();
    final role = getRole();
    return sessionId != null && sessionId.isNotEmpty && role != null;
  }

  bool isTechnician() {
    final role = getRole();
    return role == 'Technician';
  }
}
