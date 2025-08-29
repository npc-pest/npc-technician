import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'dart:async';

// Import services and providers
import 'services/auth_service.dart';
import 'services/connectivity_service.dart';
import 'services/session_monitor_service.dart';
import 'providers/technician_provider.dart';
import 'providers/order_provider.dart';

// Import screens
import 'screens/splash_screen.dart';
import 'screens/password_login_screen.dart';
import 'screens/technician_dashboard.dart';
import 'screens/profile_screen.dart';
import 'screens/order_details_screen.dart';

// Import utils
import 'utils/constants.dart';
import 'utils/theme.dart';
import 'utils/user_data_manager.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize services
  await UserDataManager().initialize();

  runApp(const TechnicianApp());
}

class TechnicianApp extends StatefulWidget {
  const TechnicianApp({super.key});

  @override
  _TechnicianAppState createState() => _TechnicianAppState();
}

class _TechnicianAppState extends State<TechnicianApp> {
  Widget _initialScreen = const SplashScreen(nextScreen: PasswordLoginScreen());
  final SessionMonitorService _sessionMonitor = SessionMonitorService();

  @override
  void initState() {
    super.initState();
    _determineInitialScreen();
  }

  Future<void> _determineInitialScreen() async {
    print('Debug: Starting splash screen...');
    await _checkExistingSession();
    print('Debug: Session check completed');
  }

  Future<void> _checkExistingSession() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? sessionId = prefs.getString('Session-ID');
    String? role = prefs.getString('role');
    String? sessionExpiry = prefs.getString('session_expiry');

    print('Debug: main.dart - Retrieved session data:');
    print('Debug: main.dart - Session ID: $sessionId');
    print('Debug: main.dart - Role: $role');
    print('Debug: main.dart - Session Expiry: $sessionExpiry');

    Widget targetScreen;

    if (sessionId == null || sessionId.isEmpty || role == null) {
      targetScreen = const PasswordLoginScreen();
    } else if (role != 'Technician') {
      await _clearSessionData();
      targetScreen = const PasswordLoginScreen();
    } else if (sessionExpiry == null) {
      await _clearSessionData();
      targetScreen = const PasswordLoginScreen();
    } else {
      try {
        DateTime expiryDateTime = DateTime.parse(sessionExpiry);
        DateTime now = DateTime.now();

        if (now.isAfter(expiryDateTime)) {
          await _clearSessionData();
          targetScreen = const PasswordLoginScreen();
        } else {
          // Session is valid, navigate to dashboard
          targetScreen = const TechnicianDashboard();

          // Start session monitoring after the widget is built
          WidgetsBinding.instance.addPostFrameCallback((_) {
            _sessionMonitor.startMonitoring(context);
          });
        }
      } catch (e) {
        print('Error parsing session expiry: $e');
        await _clearSessionData();
        targetScreen = const PasswordLoginScreen();
      }
    }

    // Update the initial screen with splash screen wrapping the target screen
    _initialScreen = SplashScreen(nextScreen: targetScreen);
  }

  Future<void> _clearSessionData() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }

  @override
  void dispose() {
    _sessionMonitor.stopMonitoring();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => TechnicianProvider()),
        ChangeNotifierProvider(create: (_) => OrderProvider()),
        ChangeNotifierProvider(create: (_) => ConnectivityService()),
      ],
      child: MaterialApp(
        title: 'NPC Technician',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        home: _initialScreen,
        routes: {
          '/password-login': (context) => const PasswordLoginScreen(),
          '/dashboard': (context) => const TechnicianDashboard(),
          '/profile': (context) => const ProfileScreen(),
        },
      ),
    );
  }
}
