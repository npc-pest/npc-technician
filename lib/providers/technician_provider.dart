import 'package:flutter/foundation.dart';
import '../models/technician.dart';
import '../services/auth_service.dart';

class TechnicianProvider with ChangeNotifier {
  final AuthService _authService = AuthService();

  Technician? _technician;
  bool _isLoading = false;
  String? _error;

  Technician? get technician => _technician;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isLoggedIn => _technician != null;

  Future<void> initialize() async {
    await _authService.initialize();
    _technician = _authService.currentTechnician;
    notifyListeners();
  }

  Future<bool> loginWithOTP(String phone, String otp) async {
    _setLoading(true);
    _clearError();

    try {
      bool success = await _authService.loginWithOTP(phone, otp);
      if (success) {
        _technician = _authService.currentTechnician;
        notifyListeners();
      }
      return success;
    } catch (e) {
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> loginWithPassword(String email, String password) async {
    _setLoading(true);
    _clearError();

    try {
      bool success = await _authService.loginWithPassword(email, password);
      if (success) {
        _technician = _authService.currentTechnician;
        notifyListeners();
      }
      return success;
    } catch (e) {
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<void> logout() async {
    _setLoading(true);

    try {
      await _authService.logout();
      _technician = null;
      notifyListeners();
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> checkLoginStatus() async {
    try {
      bool isLoggedIn = await _authService.isLoggedIn();
      if (isLoggedIn) {
        _technician = _authService.currentTechnician;
        notifyListeners();
      }
      return isLoggedIn;
    } catch (e) {
      _setError(e.toString());
      return false;
    }
  }

  Future<bool> sendOTP(String phone) async {
    _setLoading(true);
    _clearError();

    try {
      bool success = await _authService.sendOTP(phone);
      return success;
    } catch (e) {
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  void updateTechnician(Technician technician) {
    _technician = technician;
    notifyListeners();
  }

  void _setLoading(bool loading) {
    _isLoading = loading;
    notifyListeners();
  }

  void _setError(String error) {
    _error = error;
    notifyListeners();
  }

  void _clearError() {
    _error = null;
    notifyListeners();
  }

  void clearError() {
    _clearError();
  }
}
