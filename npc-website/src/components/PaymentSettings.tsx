import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { FiDollarSign, FiCreditCard, FiSave, FiAlertTriangle } from 'react-icons/fi';
import { apiService } from '../services/api';
import { API_ENDPOINTS } from '../config/api';
import { PaymentSettings, PaymentSettingsResponse } from '../types';
import toast from 'react-hot-toast';
import axios from 'axios';

const PaymentSettingsComponent: React.FC = () => {
  const [settings, setSettings] = useState<PaymentSettings>({
    cash_on_delivery_enabled: true,
    online_payment_enabled: true
  });
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchPaymentSettings();
  }, []);

  const fetchPaymentSettings = async () => {
    try {
      setIsLoading(true);
      setError(null);
      
      // Get payment settings - this is publicly accessible
      const response = await axios.get(`${process.env.REACT_APP_API_BASE_URL || 'https://npcpest.com/npc/'}${API_ENDPOINTS.GET_PAYMENT_SETTINGS}`, {
        headers: {
          'Content-Type': 'application/json'
        }
      });
      
      const data = response.data;
      if (data.status === 'success') {
        setSettings(data.payment_settings);
      } else {
        setError(data.message || 'Failed to fetch payment settings');
      }
    } catch (err: any) {
      console.error('Error fetching payment settings:', err);
      if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else {
        setError('Failed to load payment settings.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const updatePaymentSettings = async () => {
    try {
      setIsSaving(true);
      setError(null);

      // Check if user is logged in as admin
      const sessionId = localStorage.getItem('sessionId');
      const userRole = localStorage.getItem('userRole');
      
      if (!sessionId) {
        setError('Please log in as an admin to update payment settings');
        toast.error('Please log in as an admin to update payment settings');
        return;
      }
      
      if (userRole !== 'Admin') {
        setError('Only administrators can update payment settings');
        toast.error('Only administrators can update payment settings');
        return;
      }

      // Use axios directly to get the raw response
      const response = await axios.post(`${process.env.REACT_APP_API_BASE_URL || 'https://npcpest.com/npc/'}${API_ENDPOINTS.UPDATE_PAYMENT_SETTINGS}`, settings, {
        headers: {
          'Content-Type': 'application/json',
          'Session-ID': sessionId
        }
      });

      const data = response.data;
      if (data.status === 'success') {
        toast.success('Payment settings updated successfully!');
      } else {
        setError(data.message || 'Failed to update payment settings');
        toast.error(data.message || 'Failed to update payment settings');
      }
    } catch (err: any) {
      console.error('Error updating payment settings:', err);
      if (err.response?.data?.message) {
        setError(err.response.data.message);
        toast.error(err.response.data.message);
      } else {
        setError('Failed to update payment settings. Please ensure you are logged in as an admin.');
        toast.error('Failed to update payment settings. Please ensure you are logged in as an admin.');
      }
    } finally {
      setIsSaving(false);
    }
  };

  const handleToggle = (key: keyof PaymentSettings) => {
    setSettings(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
      </div>
    );
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="space-y-6"
    >
      <div className="bg-white rounded-lg shadow-sm p-6">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-gray-900">Payment Settings</h2>
          <button
            onClick={updatePaymentSettings}
            disabled={isSaving}
            className="flex items-center px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <FiSave className="w-4 h-4 mr-2" />
            {isSaving ? 'Saving...' : 'Save Settings'}
          </button>
        </div>

        {error && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div className="flex items-center">
              <FiAlertTriangle className="w-5 h-5 text-red-600 mr-2" />
              <span className="text-red-800">{error}</span>
            </div>
          </div>
        )}

        <div className="space-y-6">
          {/* Cash on Delivery Setting */}
          <div className="border border-gray-200 rounded-lg p-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <div className="p-3 bg-gray-100 rounded-lg">
                  <FiDollarSign className="w-6 h-6 text-black" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">Cash on Delivery</h3>
                  <p className="text-sm text-gray-600">Allow users to pay when service is completed</p>
                </div>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.cash_on_delivery_enabled}
                  onChange={() => handleToggle('cash_on_delivery_enabled')}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-black"></div>
              </label>
            </div>
          </div>

          {/* Online Payment Setting */}
          <div className="border border-gray-200 rounded-lg p-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <div className="p-3 bg-blue-100 rounded-lg">
                  <FiCreditCard className="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">Online Payment</h3>
                  <p className="text-sm text-gray-600">Allow users to pay securely using Razorpay</p>
                </div>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.online_payment_enabled}
                  onChange={() => handleToggle('online_payment_enabled')}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-black"></div>
              </label>
            </div>
          </div>

          {/* Warning Message */}
          {!settings.cash_on_delivery_enabled && !settings.online_payment_enabled && (
            <div className="p-4 bg-orange-50 border border-orange-200 rounded-lg">
              <div className="flex items-center">
                <FiAlertTriangle className="w-5 h-5 text-orange-600 mr-2" />
                <span className="text-orange-800 text-sm">
                  Warning: Both payment methods are disabled. Users will not be able to complete bookings.
                </span>
              </div>
            </div>
          )}

          {/* Current Status */}
          <div className="mt-6 p-4 bg-gray-50 rounded-lg">
            <h4 className="font-semibold text-gray-900 mb-2">Current Status</h4>
            <div className="space-y-2 text-sm">
              <div className="flex items-center justify-between">
                <span className="text-gray-600">Cash on Delivery:</span>
                <span className={`font-medium ${settings.cash_on_delivery_enabled ? 'text-black' : 'text-red-600'}`}>
                  {settings.cash_on_delivery_enabled ? 'Enabled' : 'Disabled'}
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-gray-600">Online Payment:</span>
                <span className={`font-medium ${settings.online_payment_enabled ? 'text-black' : 'text-red-600'}`}>
                  {settings.online_payment_enabled ? 'Enabled' : 'Disabled'}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </motion.div>
  );
};

export default PaymentSettingsComponent; 