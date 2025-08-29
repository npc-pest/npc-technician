import 'package:flutter/foundation.dart';
import 'package:connectivity_plus/connectivity_plus.dart';

class ConnectivityService with ChangeNotifier {
  bool _isConnected = true;
  ConnectivityResult _connectivityResult = ConnectivityResult.none;

  bool get isConnected => _isConnected;
  ConnectivityResult get connectivityResult => _connectivityResult;

  ConnectivityService() {
    _initConnectivity();
    _setupConnectivityListener();
  }

  Future<void> _initConnectivity() async {
    try {
      _connectivityResult = await Connectivity().checkConnectivity();
      _isConnected = _connectivityResult != ConnectivityResult.none;
      notifyListeners();
    } catch (e) {
      print('Error checking connectivity: $e');
    }
  }

  void _setupConnectivityListener() {
    Connectivity().onConnectivityChanged.listen((ConnectivityResult result) {
      _connectivityResult = result;
      _isConnected = result != ConnectivityResult.none;
      notifyListeners();
    });
  }

  Future<bool> checkConnectivity() async {
    try {
      _connectivityResult = await Connectivity().checkConnectivity();
      _isConnected = _connectivityResult != ConnectivityResult.none;
      notifyListeners();
      return _isConnected;
    } catch (e) {
      print('Error checking connectivity: $e');
      return false;
    }
  }
}
