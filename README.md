# NPC Technician App

A Flutter application designed specifically for technicians to manage orders and provide professional services for NPC Pest Control.

## Features

### 🔐 Authentication
- **OTP Login**: Secure login using phone number and OTP
- **Password Login**: Alternative login method with username/password
- **Session Management**: Automatic session handling and expiry
- **Role-based Access**: Technician-specific access control

### 📱 Dashboard
- **Order Management**: View and manage assigned orders
- **Real-time Updates**: Live order status updates
- **Search & Filter**: Advanced search and filtering capabilities
- **Statistics**: Order statistics and performance metrics

### 📋 Order Management
- **Order Status Updates**: Update order status (Assigned → Reached → Started → Completed)
- **Customer Information**: View customer details and contact information
- **Service Details**: Access comprehensive service information
- **Location Services**: Navigate to customer locations
- **Notes & Comments**: Add technician notes and comments

### 🔔 Notifications
- **Push Notifications**: Real-time order notifications
- **Firebase Integration**: Cloud messaging for instant updates
- **Local Notifications**: In-app notification system

### 📊 Analytics & Reports
- **Earnings Tracking**: Monitor daily, weekly, and monthly earnings
- **Performance Metrics**: Track completion rates and ratings
- **Order History**: Comprehensive order history and analytics

### ⚙️ Settings & Profile
- **Profile Management**: Update personal information
- **App Settings**: Customize app preferences
- **Notification Settings**: Manage notification preferences

## Technical Architecture

### State Management
- **Provider Pattern**: Efficient state management using Provider
- **Service Layer**: Clean separation of business logic
- **Repository Pattern**: Data access abstraction

### API Integration
- **RESTful APIs**: Integration with NPC backend services
- **HTTP Client**: Robust HTTP client with error handling
- **Session Management**: Secure session handling

### Local Storage
- **Shared Preferences**: Secure local data storage
- **Offline Support**: Basic offline functionality
- **Data Persistence**: Local caching for better performance

## Getting Started

### Prerequisites
- Flutter SDK (3.5.3 or higher)
- Dart SDK (3.5.3 or higher)
- Android Studio / VS Code
- Android SDK / Xcode (for mobile development)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd npc_technician
   ```

2. **Install dependencies**
   ```bash
   flutter pub get
   ```

3. **Configure Firebase** (if using Firebase features)
   - Add `google-services.json` to `android/app/`
   - Add `GoogleService-Info.plist` to `ios/Runner/`

4. **Run the app**
   ```bash
   flutter run
   ```

### Environment Setup

1. **API Configuration**
   - Update API base URL in `lib/utils/constants.dart`
   - Configure endpoints as needed

2. **Firebase Setup** (Optional)
   - Enable Firebase Cloud Messaging
   - Configure notification channels
   - Set up authentication if required

## Project Structure

```
lib/
├── main.dart                 # App entry point
├── models/                   # Data models
│   ├── technician.dart       # Technician model
│   └── order.dart           # Order model
├── providers/               # State management
│   ├── technician_provider.dart
│   └── order_provider.dart
├── screens/                 # UI screens
│   ├── splash_screen.dart
│   ├── otp_login_screen.dart
│   ├── technician_dashboard.dart
│   └── ...
├── services/                # Business logic
│   ├── auth_service.dart
│   ├── order_service.dart
│   ├── notification_service.dart
│   └── connectivity_service.dart
├── utils/                   # Utilities
│   ├── constants.dart
│   ├── theme.dart
│   └── user_data_manager.dart
└── widgets/                 # Reusable widgets
    ├── custom_button.dart
    ├── custom_text_field.dart
    ├── order_card.dart
    ├── stats_card.dart
    └── search_bar.dart
```

## API Endpoints

The app integrates with the following API endpoints:

- `POST /login.php` - User authentication
- `POST /otp_login.php` - OTP-based login
- `GET /technician_orders.php` - Fetch technician orders
- `POST /update_order_status.php` - Update order status
- `GET /technician_profile.php` - Get technician profile
- `POST /update_technician_profile.php` - Update profile
- `GET /technician_earnings.php` - Get earnings data
- `GET /technician_notifications.php` - Get notifications

## Dependencies

### Core Dependencies
- `flutter`: UI framework
- `provider`: State management
- `http`: HTTP client
- `shared_preferences`: Local storage
- `firebase_core`: Firebase core functionality
- `firebase_messaging`: Push notifications

### UI Dependencies
- `google_fonts`: Custom fonts
- `flutter_svg`: SVG support
- `shimmer`: Loading animations
- `cached_network_image`: Image caching

### Utility Dependencies
- `intl`: Internationalization
- `url_launcher`: URL handling
- `connectivity_plus`: Network connectivity
- `geolocator`: Location services

## Configuration

### Theme Configuration
The app uses a consistent theme defined in `lib/utils/theme.dart`:
- Primary Color: `#4A9589` (Green)
- Secondary Color: `#2E7D32` (Dark Green)
- Background: `#F5F5F5` (Light Gray)
- Text Colors: Various shades for hierarchy

### Constants
App-wide constants are defined in `lib/utils/constants.dart`:
- API URLs and endpoints
- Color values
- Animation durations
- Error messages
- Validation rules

## Development Guidelines

### Code Style
- Follow Flutter/Dart style guidelines
- Use meaningful variable and function names
- Add comments for complex logic
- Maintain consistent indentation

### State Management
- Use Provider for state management
- Keep providers focused and single-purpose
- Avoid deep nesting of providers
- Use Consumer widgets efficiently

### Error Handling
- Implement proper error handling for API calls
- Show user-friendly error messages
- Log errors for debugging
- Handle network connectivity issues

### Performance
- Optimize widget rebuilds
- Use const constructors where possible
- Implement proper image caching
- Minimize API calls

## Testing

### Unit Tests
```bash
flutter test
```

### Widget Tests
```bash
flutter test test/widget_test.dart
```

### Integration Tests
```bash
flutter drive --target=test_driver/app.dart
```

## Deployment

### Android
1. Update version in `pubspec.yaml`
2. Build APK: `flutter build apk --release`
3. Build App Bundle: `flutter build appbundle --release`

### iOS
1. Update version in `pubspec.yaml`
2. Build for iOS: `flutter build ios --release`
3. Archive and upload to App Store Connect

## Support

For technical support or questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## License

This project is proprietary software developed for NPC Pest Control.

## Version History

- **v1.0.0** - Initial release with core functionality
  - Authentication system
  - Order management
  - Dashboard with statistics
  - Basic notification system

---

**Note**: This app is specifically designed for NPC Pest Control technicians and integrates with their existing backend infrastructure.
