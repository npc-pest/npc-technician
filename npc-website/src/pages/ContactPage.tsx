import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { 
  FiShield, 
  FiArrowLeft, 
  FiUser, 
  FiMail, 
  FiPhone, 
  FiMessageSquare,
  FiSend,
  FiMapPin,
  FiClock,
  FiCheckCircle
} from 'react-icons/fi';
import { useAuth } from '../context/AuthContext';
import { ContactForm } from '../types';
import { apiService } from '../services/api';
import { API_ENDPOINTS } from '../config/api';
import toast from 'react-hot-toast';

const ContactPage: React.FC = () => {
  const { user } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState<ContactForm>({
    first_name: user?.name?.split(' ')[0] || '',
    last_name: user?.name?.split(' ').slice(1).join(' ') || '',
    email: user?.email || '',
    phone: user?.mobile || '',
    subject: '',
    message: ''
  });

  const subjectOptions = [
    'General Inquiry',
    'Service Booking',
    'Pricing Information',
    'Technical Support',
    'Complaint',
    'Feedback',
    'Other'
  ];

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.first_name.trim() || !formData.last_name.trim() || 
        !formData.email.trim() || !formData.phone.trim() || 
        !formData.subject.trim() || !formData.message.trim()) {
      toast.error('Please fill in all fields');
      return;
    }

    setIsSubmitting(true);
    try {
      const response = await apiService.post(API_ENDPOINTS.SUBMIT_CONTACT_QUERY, formData);
      
      if (response.status === 'success') {
        toast.success('Message sent successfully! We\'ll get back to you soon.');
        setFormData({
          first_name: user?.name?.split(' ')[0] || '',
          last_name: user?.name?.split(' ').slice(1).join(' ') || '',
          email: user?.email || '',
          phone: user?.mobile || '',
          subject: '',
          message: ''
        });
      } else {
        toast.error(response.message || 'Failed to send message');
      }
    } catch (error) {
      toast.error('Failed to send message');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => window.history.back()}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <FiArrowLeft className="w-5 h-5" />
              </button>
               <div className="w-10 h-10 bg-black rounded-lg flex items-center justify-center overflow-hidden">
                <img 
                  src="/images/logo-npc.png" 
                  alt="NPC Pest Control Logo"
                  className="w-8 h-8 object-contain"
                  onError={(e) => {
                    // Fallback to shield icon if logo fails to load
                    e.currentTarget.style.display = 'none';
                    e.currentTarget.nextElementSibling?.classList.remove('hidden');
                  }}
                />
                <FiShield className="w-6 h-6 text-white hidden" />
              </div>
              <div>
                <h1 className="text-xl font-semibold text-gray-900">npc</h1>
                <p className="text-sm text-gray-500">Professional Services</p>
              </div>
            </div>
            

          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
        >
          {/* Page Header */}
          <div className="text-center mb-6 sm:mb-8">
            <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Contact Us</h2>
            <p className="text-sm sm:text-base text-gray-600">Get in touch with our support team</p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
            {/* Contact Form */}
            <motion.div
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.6, delay: 0.2 }}
              className="bg-white rounded-lg shadow-sm p-6"
            >
              <h3 className="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6">Send us a Message</h3>
              
              <form onSubmit={handleSubmit} className="space-y-3 sm:space-y-4">
                {/* Name Fields */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                      <FiUser className="inline w-4 h-4 mr-1" />
                      First Name
                    </label>
                    <input
                      type="text"
                      name="first_name"
                      value={formData.first_name}
                      onChange={handleInputChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm sm:text-base"
                      placeholder="Enter your first name"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                      Last Name
                    </label>
                    <input
                      type="text"
                      name="last_name"
                      value={formData.last_name}
                      onChange={handleInputChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm sm:text-base"
                      placeholder="Enter your last name"
                    />
                  </div>
                </div>

                {/* Email */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                    <FiMail className="inline w-4 h-4 mr-1" />
                    Email Address
                  </label>
                  <input
                    type="text"
                    name="email"
                    value={formData.email}
                    onChange={handleInputChange}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm sm:text-base"
                    placeholder="Enter your email address"
                  />
                </div>

                {/* Phone */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                    <FiPhone className="inline w-4 h-4 mr-1" />
                    Phone Number
                  </label>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleInputChange}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm sm:text-base"
                    placeholder="Enter your phone number"
                  />
                </div>

                {/* Subject */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                    <FiMessageSquare className="inline w-4 h-4 mr-1" />
                    Subject
                  </label>
                  <select
                    name="subject"
                    value={formData.subject}
                    onChange={handleInputChange}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm sm:text-base"
                  >
                    <option value="">Select a subject</option>
                    {subjectOptions.map((subject) => (
                      <option key={subject} value={subject}>{subject}</option>
                    ))}
                  </select>
                </div>

                {/* Message */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                    Message
                  </label>
                  <textarea
                    name="message"
                    value={formData.message}
                    onChange={handleInputChange}
                    rows={4}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm sm:text-base"
                    placeholder="Tell us how we can help you..."
                  />
                </div>

                {/* Submit Button */}
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="w-full btn btn-primary"
                >
                  {isSubmitting ? (
                    <div className="flex items-center justify-center">
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                      Sending Message...
                    </div>
                  ) : (
                    <div className="flex items-center justify-center">
                      <FiSend className="w-4 h-4 mr-2" />
                      Send Message
                    </div>
                  )}
                </button>
              </form>
            </motion.div>

            {/* Contact Information */}
            <motion.div
              initial={{ opacity: 0, x: 20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.6, delay: 0.4 }}
              className="space-y-6"
            >
              {/* Contact Info Card */}
              <div className="bg-white rounded-lg shadow-sm p-4 sm:p-6">
                <h3 className="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6">Contact Information</h3>
                
                <div className="space-y-3 sm:space-y-4">
                  <div className="flex items-start space-x-2 sm:space-x-3">
                    <div className="w-6 h-6 sm:w-8 sm:h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <FiPhone className="w-3 h-3 sm:w-4 sm:h-4 text-black" />
                    </div>
                    <div>
                      <p className="font-medium text-gray-900 text-sm sm:text-base">Phone</p>
                      <p className="text-gray-600 text-sm sm:text-base">+91 8111043335</p>
                    </div>
                  </div>

                  <div className="flex items-start space-x-2 sm:space-x-3">
                    <div className="w-6 h-6 sm:w-8 sm:h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <FiMail className="w-3 h-3 sm:w-4 sm:h-4 text-black" />
                    </div>
                    <div>
                      <p className="font-medium text-gray-900 text-sm sm:text-base">Email</p>
                      <p className="text-gray-600 text-sm sm:text-base">info@npcpest.com</p>
                    </div>
                  </div>

                  <div className="flex items-start space-x-2 sm:space-x-3">
                    <div className="w-6 h-6 sm:w-8 sm:h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <FiMapPin className="w-3 h-3 sm:w-4 sm:h-4 text-black" />
                    </div>
                    <div>
                      <p className="font-medium text-gray-900 text-sm sm:text-base">Address</p>
                      <p className="text-gray-600 text-sm sm:text-base">
                        NPC, NO. 158, Murugan Kovil Street,<br />
                        Vanashakthi Nagar, Kolather, Chennai - 99.
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start space-x-2 sm:space-x-3">
                    <div className="w-6 h-6 sm:w-8 sm:h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <FiClock className="w-3 h-3 sm:w-4 sm:h-4 text-black" />
                    </div>
                    <div>
                      <p className="font-medium text-gray-900 text-sm sm:text-base">Business Hours</p>
                      <p className="text-gray-600 text-sm sm:text-base">
                        Monday - Saturday: 8:00 AM - 8:00 PM<br />
                        Sunday: 9:00 AM - 6:00 PM
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Why Choose Us Card */}
              <div className="bg-white rounded-lg shadow-sm p-4 sm:p-6">
                <h3 className="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6">Why Choose NPC?</h3>
                
                <div className="space-y-2 sm:space-y-3">
                  <div className="flex items-center space-x-2 sm:space-x-3">
                    <FiCheckCircle className="w-4 h-4 sm:w-5 sm:h-5 text-black flex-shrink-0" />
                    <span className="text-gray-700 text-sm sm:text-base">Professional Home Facility Services</span>
                  </div>
                  <div className="flex items-center space-x-2 sm:space-x-3">
                    <FiCheckCircle className="w-4 h-4 sm:w-5 sm:h-5 text-black flex-shrink-0" />
                    <span className="text-gray-700 text-sm sm:text-base">Same day service available</span>
                  </div>
                  <div className="flex items-center space-x-2 sm:space-x-3">
                    <FiCheckCircle className="w-4 h-4 sm:w-5 sm:h-5 text-black flex-shrink-0" />
                    <span className="text-gray-700 text-sm sm:text-base">Licensed and insured technicians</span>
                  </div>
                  <div className="flex items-center space-x-2 sm:space-x-3">
                    <FiCheckCircle className="w-4 h-4 sm:w-5 sm:h-5 text-black flex-shrink-0" />
                    <span className="text-gray-700 text-sm sm:text-base">Satisfaction guaranteed</span>
                  </div>
                  <div className="flex items-center space-x-2 sm:space-x-3">
                    <FiCheckCircle className="w-4 h-4 sm:w-5 sm:h-5 text-black flex-shrink-0" />
                    <span className="text-gray-700 text-sm sm:text-base">24/7 customer support</span>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        </motion.div>
      </div>
    </div>
  );
};

export default ContactPage; 