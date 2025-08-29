class AppConstants {
  // API Base URL
  static const String baseUrl = "https://npcpest.com/npc/";

  // API Endpoints
  static const String loginEndpoint = "technicianlogin.php";
  static const String otpLoginEndpoint = "verify_otp.php";
  static const String sendOtpEndpoint = "send_otp.php";
  static const String technicianOrdersEndpoint = "technician_orders.php";
  static const String updateOrderStatusEndpoint =
      "technician_status_update_final.php";
  static const String technicianProfileEndpoint = "gettechnicianprofile.php";
  static const String updateProfileEndpoint = "update_technician_profile.php";
  static const String earningsEndpoint = "technician_earnings.php";
  static const String notificationsEndpoint = "technician_notifications.php";
  static const String scheduleEndpoint = "technician_schedule.php";

  // App Colors
  static const int primaryColorValue = 0xFF4A9589;
  static const int secondaryColorValue = 0xFF2E7D32;
  static const int accentColorValue = 0xFF81C784;
  static const int backgroundColorValue = 0xFFF5F5F5;
  static const int cardColorValue = 0xFFFFFFFF;
  static const int textColorValue = 0xFF212121;
  static const int textLightColorValue = 0xFF757575;
  static const int errorColorValue = 0xFFD32F2F;
  static const int successColorValue = 0xFF388E3C;
  static const int warningColorValue = 0xFFFFA000;

  // Order Status
  static const String statusAssigned = "assigned";
  static const String statusReached = "reached";
  static const String statusStarted = "started";
  static const String statusCompleted = "completed";
  static const String statusCancelled = "cancelled";

  // Shared Preferences Keys
  static const String sessionIdKey = "Session-ID";
  static const String roleKey = "role";
  static const String sessionExpiryKey = "session_expiry";
  static const String technicianIdKey = "technician_id";
  static const String technicianNameKey = "technician_name";
  static const String technicianPhoneKey = "technician_phone";
  static const String fcmTokenKey = "fcm_token";

  // Animation Durations
  static const Duration splashDuration = Duration(seconds: 3);
  static const Duration animationDuration = Duration(milliseconds: 300);
  static const Duration longAnimationDuration = Duration(milliseconds: 500);

  // UI Constants
  static const double defaultPadding = 16.0;
  static const double smallPadding = 8.0;
  static const double largePadding = 24.0;
  static const double borderRadius = 12.0;
  static const double cardElevation = 4.0;

  // Font Sizes
  static const double headingFontSize = 24.0;
  static const double titleFontSize = 20.0;
  static const double subtitleFontSize = 16.0;
  static const double bodyFontSize = 14.0;
  static const double captionFontSize = 12.0;

  // Image Assets
  static const String logoPath = "assets/images/logo.png";
  static const String placeholderImagePath = "assets/images/placeholder.png";
  static const String defaultAvatarPath = "assets/images/default_avatar.png";

  // Error Messages
  static const String networkError =
      "Network error. Please check your connection.";
  static const String serverError = "Server error. Please try again later.";
  static const String sessionExpired = "Session expired. Please login again.";
  static const String invalidCredentials =
      "Invalid credentials. Please try again.";
  static const String somethingWentWrong =
      "Something went wrong. Please try again.";

  // Success Messages
  static const String loginSuccess = "Login successful!";
  static const String profileUpdated = "Profile updated successfully!";
  static const String statusUpdated = "Order status updated successfully!";
  static const String logoutSuccess = "Logged out successfully!";

  // Validation Messages
  static const String phoneRequired = "Phone number is required";
  static const String invalidPhone = "Please enter a valid phone number";
  static const String otpRequired = "OTP is required";
  static const String invalidOtp = "Please enter a valid 6-digit OTP";
  static const String nameRequired = "Name is required";
  static const String emailRequired = "Email is required";
  static const String invalidEmail = "Please enter a valid email address";
}
