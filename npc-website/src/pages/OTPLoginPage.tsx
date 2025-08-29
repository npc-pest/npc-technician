import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { FiShield, FiMail, FiArrowLeft, FiRefreshCw } from 'react-icons/fi';
import { useAuth } from '../context/AuthContext';
import { getApiUrl } from '../config/api';
import { API_ENDPOINTS } from '../config/api';
import { toast } from 'react-hot-toast';

interface OTPForm {
  mobile_number: string;
  otp: string;
}

const OTPLoginPage: React.FC = () => {
  const { loginWithOTP } = useAuth();
  const navigate = useNavigate();
  
  const [isLoading, setIsLoading] = useState(false);
  const [isSendingOTP, setIsSendingOTP] = useState(false);
  const [showOTPInput, setShowOTPInput] = useState(false);
  const [countdown, setCountdown] = useState(0);
  const [formData, setFormData] = useState<OTPForm>({
    mobile_number: '',
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

  const validateMobileNumber = (mobile: string): boolean => {
    return /^[6-9]\d{9}$/.test(mobile);
  };

  const validateForm = (): boolean => {
    const newErrors: Partial<OTPForm> = {};

    if (!formData.mobile_number.trim()) {
      newErrors.mobile_number = 'Mobile number is required';
    } else if (!validateMobileNumber(formData.mobile_number)) {
      newErrors.mobile_number = 'Please enter a valid Indian mobile number';
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
    if (!validateMobileNumber(formData.mobile_number)) {
      setErrors({ mobile_number: 'Please enter a valid Indian mobile number' });
      return;
    }

    setIsSendingOTP(true);
    try {
      
      const response = await fetch(getApiUrl(API_ENDPOINTS.SEND_OTP), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          mobile_number: formData.mobile_number
        }),
      });

      const data = await response.json();

      if (data.Status === 'Success' || data.status === 'success') {
        setShowOTPInput(true);
        setCountdown(60); // 60 seconds countdown
        toast.success('OTP sent successfully!');
      } else {
        toast.error(data.message || data.Message || 'Failed to send OTP');
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
      const success = await loginWithOTP(formData.mobile_number, formData.otp);
      if (success) {
        navigate('/dashboard');
      }
    } catch (error) {
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
    <div className="min-h-screen bg-gradient-to-br from-teal-50 via-blue-50 to-indigo-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      
      <div className="max-w-md w-full space-y-8">
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
        >
          {/* Back Button */}
          <div className="mb-6">
            <Link
              to="/"
                              className="inline-flex items-center text-sm text-black hover:text-gray-800 transition-colors"
            >
              <FiArrowLeft className="mr-2" />
              Back to dashboard
            </Link>
          </div>

          {/* Logo */}
          <div className="text-center">
            <div className="flex justify-center">
                              <div className="w-16 h-16 bg-black rounded-2xl flex items-center justify-center overflow-hidden">
                <img 
                  src="/images/logo-npc.png" 
                  alt="NPC Pest Control Logo"
                  className="w-12 h-12 object-contain"
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                    e.currentTarget.nextElementSibling?.classList.remove('hidden');
                  }}
                />
                <FiShield className="w-8 h-8 text-white hidden" />
              </div>
            </div>
            <h2 className="mt-6 text-3xl font-bold text-gray-900">
              {showOTPInput ? 'Enter OTP' : 'Login with OTP'}
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              {showOTPInput 
                ? 'Enter the 6-digit code sent to your mobile'
                : 'Enter your mobile number to login or create account'
              }
            </p>
          </div>

          {/* OTP Login Form */}
          <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
            <div className="space-y-4">
              {/* Mobile Number */}
              <div>
                <label htmlFor="mobile_number" className="block text-sm font-medium text-gray-700 mb-2">
                  Mobile Number
                </label>
                <div className="relative">
                  <FiMail className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    id="mobile_number"
                    name="mobile_number"
                    type="tel"
                    autoComplete="tel"
                    required
                    value={formData.mobile_number}
                    onChange={handleInputChange}
                    disabled={showOTPInput}
                    className={`w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors ${
                      errors.mobile_number ? 'border-red-500' : 'border-gray-300'
                    } ${showOTPInput ? 'bg-gray-50' : ''}`}
                    placeholder="Enter your mobile number"
                  />
                </div>
                {errors.mobile_number && (
                  <p className="mt-1 text-sm text-red-600">{errors.mobile_number}</p>
                )}
              </div>

              {/* OTP Input */}
              {showOTPInput && (
                <div>
                  <label htmlFor="otp" className="block text-sm font-medium text-gray-700 mb-2">
                    OTP Code
                  </label>
                  <div className="relative">
                    <input
                      id="otp"
                      name="otp"
                      type="text"
                      autoComplete="one-time-code"
                      required
                      maxLength={6}
                      value={formData.otp}
                      onChange={handleInputChange}
                      className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors text-center text-lg tracking-widest ${
                        errors.otp ? 'border-red-500' : 'border-gray-300'
                      }`}
                      placeholder="000000"
                    />
                  </div>
                  {errors.otp && (
                    <p className="mt-1 text-sm text-red-600">{errors.otp}</p>
                  )}
                  
                  {/* Resend OTP */}
                  <div className="mt-3 text-center">
                    <button
                      type="button"
                      onClick={handleResendOTP}
                      disabled={countdown > 0 || isSendingOTP}
                      className="text-sm text-black hover:text-gray-800 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors"
                    >
                      {isSendingOTP ? (
                        <div className="flex items-center justify-center">
                          <FiRefreshCw className="animate-spin mr-1" />
                          Sending...
                        </div>
                      ) : countdown > 0 ? (
                        `Resend OTP in ${countdown}s`
                      ) : (
                        'Resend OTP'
                      )}
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Submit Button */}
            <div>
              <button
                type="submit"
                disabled={isLoading || isSendingOTP}
                className="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {isLoading ? (
                  <div className="flex items-center">
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    {showOTPInput ? 'Verifying...' : 'Sending OTP...'}
                  </div>
                ) : (
                  showOTPInput ? 'Verify OTP' : 'Send OTP'
                )}
              </button>
            </div>

            {/* Staff Login Option */}
            <div className="text-center">
              <p className="text-sm text-gray-600">
                Are you staff?{' '}
                <Link
                  to="/login"
                  className="font-medium text-black hover:text-gray-800 transition-colors"
                >
                  Staff login
                </Link>
              </p>
            </div>
          </form>
        </motion.div>
      </div>
    </div>
  );
};

export default OTPLoginPage; 