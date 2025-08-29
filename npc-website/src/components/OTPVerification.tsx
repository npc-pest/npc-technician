import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { FiShield, FiMail, FiArrowLeft, FiRefreshCw, FiPhone } from 'react-icons/fi';
import { toast } from 'react-hot-toast';
import { getApiUrl } from '../config/api';
import { API_ENDPOINTS } from '../config/api';

interface OTPVerificationProps {
  onSuccess: (userData: any) => void;
  onBack: () => void;
}

interface OTPForm {
  phone: string;
  otp: string;
}

const OTPVerification: React.FC<OTPVerificationProps> = ({ onSuccess, onBack }) => {
  const [isLoading, setIsLoading] = useState(false);
  const [isSendingOTP, setIsSendingOTP] = useState(false);
  const [showOTPInput, setShowOTPInput] = useState(false);
  const [countdown, setCountdown] = useState(0);
  const [sessionId, setSessionId] = useState('');
  const [formData, setFormData] = useState<OTPForm>({
    phone: '',
    otp: ''
  });
  const [errors, setErrors] = useState<Partial<OTPForm>>({});

  // Countdown timer for resend OTP
  useEffect(() => {
    let timer: NodeJS.Timeout;
    if (countdown > 0) {
      timer = setTimeout(() => setCountdown(countdown - 1), 1000);
    }
    return () => clearTimeout(timer);
  }, [countdown]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear error when user starts typing
    if (errors[name as keyof OTPForm]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const validatePhoneNumber = (phone: string): boolean => {
    // Remove country code if present
    const cleanPhone = phone.replace(/^(\+91|91)/, '');
    return /^[6-9]\d{9}$/.test(cleanPhone);
  };

  const validateForm = (): boolean => {
    const newErrors: Partial<OTPForm> = {};

    if (!formData.phone.trim()) {
      newErrors.phone = 'Phone number is required';
    } else if (!validatePhoneNumber(formData.phone)) {
      newErrors.phone = 'Please enter a valid Indian mobile number';
    }

    if (showOTPInput && !formData.otp.trim()) {
      newErrors.otp = 'OTP is required';
    } else if (showOTPInput && formData.otp.length !== 6) {
      newErrors.otp = 'OTP must be 6 digits';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const sendOTP = async () => {
    if (!validateForm()) {
      return;
    }

    setIsSendingOTP(true);
    try {
      const response = await fetch(getApiUrl(API_ENDPOINTS.SEND_OTP_SESSION), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          phone: formData.phone
        }),
      });

      const data = await response.json();

      if (data.Status === 'Success') {
        setSessionId(data.Details);
        setShowOTPInput(true);
        setCountdown(60); // 60 seconds countdown
        toast.success('OTP sent successfully!');
      } else {
        toast.error(data.Details || 'Failed to send OTP');
      }
    } catch (error) {
      toast.error('Failed to send OTP. Please try again.');
    } finally {
      setIsSendingOTP(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    if (!showOTPInput) {
      // First step: Send OTP
      await sendOTP();
      return;
    }

    // Second step: Verify OTP
    setIsLoading(true);
    try {
      const response = await fetch(getApiUrl(API_ENDPOINTS.VERIFY_OTP_SESSION), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          sessionId: sessionId,
          otp: formData.otp,
          fcm_token: 'web_token'
        }),
      });

      const data = await response.json();

      if (data.Status === 'Success') {
        // Store session data
        localStorage.setItem('sessionId', data.sessionid);
        localStorage.setItem('userRole', 'User');
        if (data.session_expiry) {
          localStorage.setItem('sessionExpiry', data.session_expiry);
        }

        toast.success('Login successful!');
        onSuccess(data.data);
      } else {
        toast.error(data.Details || 'Invalid OTP');
      }
    } catch (error) {
      console.error('Login error:', error);
      toast.error('Login failed. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleResendOTP = async () => {
    if (countdown > 0) return;
    await sendOTP();
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5 }}
      className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4"
    >
      <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="flex items-center justify-center mb-4">
            <div className="bg-blue-100 p-3 rounded-full">
              <FiShield className="text-3xl text-blue-600" />
            </div>
          </div>
          <h2 className="text-2xl font-bold text-gray-800 mb-2">
            {showOTPInput ? 'Verify OTP' : 'Phone Verification'}
          </h2>
          <p className="text-gray-600">
            {showOTPInput 
              ? 'Enter the 6-digit code sent to your phone'
              : 'Enter your phone number to receive OTP'
            }
          </p>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-6">
          {!showOTPInput ? (
            // Phone Number Input
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Phone Number
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <FiPhone className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  type="tel"
                  name="phone"
                  value={formData.phone}
                  onChange={handleInputChange}
                  placeholder="Enter your mobile number"
                  className={`w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                    errors.phone ? 'border-red-500' : 'border-gray-300'
                  }`}
                />
              </div>
              {errors.phone && (
                <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
              )}
            </div>
          ) : (
            // OTP Input
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                OTP Code
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <FiShield className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  type="text"
                  name="otp"
                  value={formData.otp}
                  onChange={handleInputChange}
                  placeholder="Enter 6-digit OTP"
                  maxLength={6}
                  className={`w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-center text-lg font-mono ${
                    errors.otp ? 'border-red-500' : 'border-gray-300'
                  }`}
                />
              </div>
              {errors.otp && (
                <p className="mt-1 text-sm text-red-600">{errors.otp}</p>
              )}
            </div>
          )}

          {/* Submit Button */}
          <button
            type="submit"
            disabled={isLoading || isSendingOTP}
            className="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? (
              <div className="flex items-center justify-center">
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                Verifying...
              </div>
            ) : isSendingOTP ? (
              <div className="flex items-center justify-center">
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                Sending OTP...
              </div>
            ) : showOTPInput ? (
              'Verify OTP'
            ) : (
              'Send OTP'
            )}
          </button>

          {/* Resend OTP */}
          {showOTPInput && (
            <div className="text-center">
              <p className="text-sm text-gray-600 mb-2">
                Didn't receive the code?
              </p>
              <button
                type="button"
                onClick={handleResendOTP}
                disabled={countdown > 0}
                className="text-blue-600 hover:text-blue-700 font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center mx-auto"
              >
                <FiRefreshCw className={`h-4 w-4 mr-1 ${countdown > 0 ? 'animate-spin' : ''}`} />
                {countdown > 0 ? `Resend in ${countdown}s` : 'Resend OTP'}
              </button>
            </div>
          )}

          {/* Back Button */}
          <div className="text-center">
            <button
              type="button"
              onClick={onBack}
              className="text-gray-600 hover:text-gray-700 font-medium text-sm flex items-center justify-center mx-auto"
            >
              <FiArrowLeft className="h-4 w-4 mr-1" />
              Back to Login
            </button>
          </div>
        </form>
      </div>
    </motion.div>
  );
};

export default OTPVerification;
