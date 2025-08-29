import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/technician_provider.dart';
import '../utils/theme.dart';

class OTPLoginScreen extends StatefulWidget {
  const OTPLoginScreen({super.key});

  @override
  State<OTPLoginScreen> createState() => _OTPLoginScreenState();
}

class _OTPLoginScreenState extends State<OTPLoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _otpController = TextEditingController();

  bool _showOTPInput = false;
  bool _isLoading = false;
  bool _isSendingOTP = false;
  int _countdown = 0;
  String? _phoneError;
  String? _otpError;

  @override
  void initState() {
    super.initState();
    _startCountdown();
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _otpController.dispose();
    super.dispose();
  }

  void _startCountdown() {
    if (_countdown > 0) {
      Future.delayed(const Duration(seconds: 1), () {
        if (mounted) {
        setState(() {
            _countdown--;
          });
          _startCountdown();
        }
      });
    }
  }

  bool _validatePhone(String phone) {
    return RegExp(r'^[6-9]\d{9}$').hasMatch(phone);
  }

  Future<void> _sendOTP() async {
    if (!_validatePhone(_phoneController.text)) {
      setState(() {
        _phoneError = 'Please enter a valid Indian mobile number';
      });
      return;
    }

    setState(() {
      _isSendingOTP = true;
      _phoneError = null;
    });

    try {
      final technicianProvider =
          Provider.of<TechnicianProvider>(context, listen: false);
      final success = await technicianProvider.sendOTP(_phoneController.text);

      if (success) {
        setState(() {
          _showOTPInput = true;
          _countdown = 60;
        });
        _startCountdown();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('OTP sent successfully!'),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Failed to send OTP. Please try again.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isSendingOTP = false;
      });
    }
  }

  Future<void> _resendOTP() async {
    if (_countdown > 0) return;
    await _sendOTP();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _otpError = null;
    });

    try {
      final technicianProvider =
          Provider.of<TechnicianProvider>(context, listen: false);
      final success = await technicianProvider.loginWithOTP(
        _phoneController.text,
        _otpController.text,
      );

      if (success) {
        Navigator.of(context).pushReplacementNamed('/dashboard');
      } else {
        setState(() {
          _otpError = 'Invalid OTP. Please try again.';
        });
      }
    } catch (e) {
        setState(() {
        _otpError = 'Login failed. Please try again.';
        });
    } finally {
        setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Color(0xFFE0F2F1), // teal-50
              Color(0xFFE3F2FD), // blue-50
              Color(0xFFE8EAF6), // indigo-50
            ],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Form(
              key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                  const SizedBox(height: 40),

                  // Logo
                Center(
                    child: Container(
                      width: 64,
                      height: 64,
                        decoration: BoxDecoration(
                        color: Colors.black,
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: const Icon(
                        Icons.shield,
                          color: Colors.white,
                        size: 32,
                      ),
                    ),
                  ),

                      const SizedBox(height: 24),

                  // Title
                      Text(
                    _showOTPInput ? 'Enter OTP' : 'Login with OTP',
                    style: const TextStyle(
                      fontSize: 28,
                                  fontWeight: FontWeight.bold,
                      color: Color(0xFF111827), // gray-900
                                ),
                    textAlign: TextAlign.center,
                      ),

                      const SizedBox(height: 8),

                  // Subtitle
                      Text(
                    _showOTPInput
                        ? 'Enter the 6-digit code sent to your mobile'
                        : 'Enter your mobile number to login or create account',
                    style: const TextStyle(
                      fontSize: 14,
                      color: Color(0xFF6B7280), // gray-600
                    ),
                    textAlign: TextAlign.center,
                  ),

                  const SizedBox(height: 32),

                  // Phone Number Input
                  if (!_showOTPInput) ...[
                    TextFormField(
                      controller: _phoneController,
                      keyboardType: TextInputType.phone,
                      decoration: InputDecoration(
                        labelText: 'Mobile Number',
                        labelStyle: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF374151), // gray-700
                        ),
                        prefixIcon: const Icon(
                          Icons.phone,
                          color: Color(0xFF9CA3AF), // gray-400
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide: const BorderSide(
                              color: Color(0xFFD1D5DB)), // gray-300
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide: const BorderSide(
                              color: Color(0xFFD1D5DB)), // gray-300
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide:
                              const BorderSide(color: Colors.black, width: 2),
                        ),
                        errorBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide:
                              const BorderSide(color: Colors.red, width: 1),
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 12),
                      ),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Mobile number is required';
                        }
                        if (!_validatePhone(value)) {
                          return 'Please enter a valid Indian mobile number';
                        }
                        if (_phoneError != null) {
                          return _phoneError;
                        }
                        return null;
                      },
                    ),

                    const SizedBox(height: 24),

                    // Send OTP Button
                    SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: _isSendingOTP ? null : _sendOTP,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.black,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                          elevation: 0,
                        ),
                        child: _isSendingOTP
                            ? const Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      valueColor: AlwaysStoppedAnimation<Color>(
                                          Colors.white),
                                    ),
                                  ),
                                  SizedBox(width: 8),
                                  Text('Sending OTP...'),
                                ],
                              )
                            : const Text(
                                'Send OTP',
                                  style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                            ),

                    // Password Login Option (matching website)
                    const SizedBox(height: 24),
                    const Divider(),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'Prefer password login? ',
                                  style: TextStyle(
                            fontSize: 14,
                            color: Color(0xFF6B7280), // gray-600
                          ),
                        ),
                        TextButton(
                          onPressed: () {
                            Navigator.of(context)
                                .pushReplacementNamed('/password-login');
                          },
                          child: const Text(
                            'Login with Password',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w500,
                              color: Colors.black,
                              ),
                            ),
                          ),
                        ],
                      ),
                  ],

                  // OTP Input
                  if (_showOTPInput) ...[
                    TextFormField(
                            controller: _otpController,
                      keyboardType: TextInputType.number,
                      maxLength: 6,
                      decoration: InputDecoration(
                            labelText: 'OTP',
                        labelStyle: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF374151), // gray-700
                        ),
                        prefixIcon: const Icon(
                          Icons.lock,
                          color: Color(0xFF9CA3AF), // gray-400
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide: const BorderSide(
                              color: Color(0xFFD1D5DB)), // gray-300
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide: const BorderSide(
                              color: Color(0xFFD1D5DB)), // gray-300
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide:
                              const BorderSide(color: Colors.black, width: 2),
                        ),
                        errorBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide:
                              const BorderSide(color: Colors.red, width: 1),
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 12),
                        counterText: '',
                      ),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'OTP is required';
                        }
                        if (value.length != 6) {
                          return 'OTP must be 6 digits';
                        }
                        if (_otpError != null) {
                          return _otpError;
                        }
                        return null;
                      },
                    ),

                          const SizedBox(height: 16),

                    // Resend OTP
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                          "Didn't receive the code? ",
                                style: TextStyle(
                            fontSize: 14,
                            color: Color(0xFF6B7280), // gray-600
                          ),
                        ),
                        if (_countdown > 0)
                          Text(
                            'Resend in $_countdown seconds',
                            style: const TextStyle(
                              fontSize: 14,
                              color: Color(0xFF6B7280), // gray-600
                            ),
                          )
                        else
                          TextButton(
                            onPressed: _resendOTP,
                            child: const Text(
                              'Resend OTP',
                                  style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w500,
                                color: Colors.black,
                                  ),
                                ),
                              ),
                            ],
                          ),

                    const SizedBox(height: 24),

                    // Login Button
                    SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _login,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.black,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                          elevation: 0,
                        ),
                        child: _isLoading
                            ? const Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      valueColor: AlwaysStoppedAnimation<Color>(
                                          Colors.white),
                                    ),
                                  ),
                                  SizedBox(width: 8),
                                  Text('Signing in...'),
                                ],
                              )
                            : const Text(
                                'Sign in',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                      ),
                    ),

                    const SizedBox(height: 24),

                    // Back to phone input
                    TextButton(
                      onPressed: () {
                        setState(() {
                          _showOTPInput = false;
                          _otpController.clear();
                          _countdown = 0;
                        });
                      },
                      child: const Text(
                        '← Back to phone number',
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.black,
                        ),
                  ),
                ),
              ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
