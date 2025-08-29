import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  FiArrowLeft, 
  FiShield, 
  FiInfo, 
  FiFolder, 
  FiShare2, 
  FiLock, 
  FiCheckCircle, 
  FiUsers, 
  FiRefreshCw, 
  FiMail, 
  FiPhone, 
  FiMapPin,
  FiChevronDown,
  FiChevronUp,
  FiGlobe,
  FiAward,
  FiUser,
  FiFileText,
  FiDollarSign,
  FiClock,
  FiAlertCircle,
  FiCheckSquare
} from 'react-icons/fi';
import { motion } from 'framer-motion';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../services/api';
import { API_BASE_URL } from '../config/api';

interface SectionProps {
  title: string;
  icon: React.ReactNode;
  content: string[];
  isExpanded: boolean;
  onToggle: () => void;
  index: number;
}

const Section: React.FC<SectionProps> = ({ title, icon, content, isExpanded, onToggle, index }) => (
  <motion.div
    initial={{ opacity: 0, y: 20 }}
    animate={{ opacity: 1, y: 0 }}
    transition={{ 
      duration: 0.6, 
      delay: index * 0.1
    }}
    className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow"
  >
    <button
      onClick={onToggle}
      className="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
    >
      <div className="flex items-center space-x-4">
        <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
          <div className="text-blue-600">
            {icon}
          </div>
        </div>
        <div>
          <h3 className="text-lg font-semibold text-gray-900">
            {title}
          </h3>
        </div>
      </div>
      <div className="text-blue-600">
        {isExpanded ? <FiChevronUp className="w-5 h-5" /> : <FiChevronDown className="w-5 h-5" />}
      </div>
    </button>
    
    {isExpanded && (
      <motion.div
        initial={{ height: 0, opacity: 0 }}
        animate={{ height: 'auto', opacity: 1 }}
        exit={{ height: 0, opacity: 0 }}
        transition={{ duration: 0.3 }}
        className="overflow-hidden"
      >
        <div className="px-6 pb-6">
          <div className="space-y-3">
            {content.map((item, itemIndex) => (
              <motion.div 
                key={itemIndex} 
                className="flex items-start space-x-3"
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: itemIndex * 0.1 }}
              >
                <div className="w-2 h-2 bg-blue-600 rounded-full mt-2 flex-shrink-0"></div>
                <p className="text-gray-700 leading-relaxed text-sm">{item}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </motion.div>
    )}
  </motion.div>
);

interface UserProfile {
  customer_name?: string;
  email_id?: string;
  mobile_number?: string;
  profile_pic?: string;
}

const PrivacyPolicy: React.FC = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [expandedSections, setExpandedSections] = useState<{ [key: string]: boolean }>({
    'Information We Collect': true,
  });
  const [userProfile, setUserProfile] = useState<UserProfile>({});

  // Fetch user profile
  const fetchUserProfile = async () => {
    try {
      const response = await apiService.getProfile();
      if (response.status === 'success' && response.data) {
        setUserProfile(response.data);
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
    }
  };

  // Fetch profile on component mount
  useEffect(() => {
    fetchUserProfile();
  }, []);

  const toggleSection = (title: string) => {
    setExpandedSections(prev => ({
      ...prev,
      [title]: !prev[title]
    }));
  };

  const sections = [
    {
      title: 'Information We Collect',
      icon: <FiFolder className="w-5 h-5" />,
      content: [
        'Personal Information: Name, contact details, address, and payment information necessary to provide our pest control services.',
        'Service Information: Details about your property, pest issues, service history, and preferences.',
        'Technical Information: Device details, IP address, app usage patterns, and other analytics to improve our service.'
      ]
    },
    {
      title: 'How We Use Your Information',
      icon: <FiInfo className="w-5 h-5" />,
      content: [
        'Provide and improve our pest control services',
        'Process payments and maintain service records',
        'Communicate with you about appointments, promotions, and service updates',
        'Analyze usage patterns to enhance user experience',
        'Comply with legal obligations and protect our rights'
      ]
    },
    {
      title: 'Information Sharing and Disclosure',
      icon: <FiShare2 className="w-5 h-5" />,
      content: [
        'Service Providers: We may share information with trusted third-party service providers who assist us in operating our business and servicing you.',
        'Business Transfers: If NATIONAL PEST CONTROL is acquired or merges with another company, your information may be transferred to the new owners.',
        'Legal Requirements: We may disclose information if required by law or to protect the rights, property, or safety of NATIONAL PEST CONTROL, our customers, or others.'
      ]
    },
    {
      title: 'Data Security',
      icon: <FiLock className="w-5 h-5" />,
      content: [
        'We implement industry-standard security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction.',
        'Despite our efforts, no method of transmission over the Internet or electronic storage is 100% secure. We cannot guarantee absolute security.'
      ]
    },
    {
      title: 'Your Rights',
      icon: <FiCheckCircle className="w-5 h-5" />,
      content: [
        'Access, correct, or delete your personal information',
        'Object to or restrict certain processing of your data',
        'Withdraw consent at any time where we rely on consent to process your information',
        'Request portability of your personal data where technically feasible'
      ]
    },
    {
      title: 'Children\'s Privacy',
      icon: <FiUsers className="w-5 h-5" />,
      content: [
        'Our services are not directed to individuals under 18 years of age. We do not knowingly collect personal information from children. If you believe we have collected personal information from a child, please contact us immediately.'
      ]
    },
    {
      title: 'Terms and Conditions',
      icon: <FiFileText className="w-5 h-5" />,
      content: [
        'Service Agreement: By booking our services, you agree to the terms and conditions outlined by NATIONAL PEST CONTROL.',
        'Service Scheduling: We reserve the right to reschedule appointments due to weather conditions, technician availability, or other unforeseen circumstances.',
        'Property Access: You must provide safe and reasonable access to your property for our technicians to perform the required services.',
        'Service Guarantee: We provide a 30-day service guarantee for most treatments. If pests return within this period, we will provide a follow-up treatment at no additional cost.',
        'Liability: NATIONAL PEST CONTROL is not liable for any damage to property that occurs during normal service operations, unless caused by gross negligence.',
        'Modifications: We reserve the right to modify these terms and conditions at any time. Continued use of our services constitutes acceptance of any changes.'
      ]
    },
    {
      title: 'Refund and Cancellation Policy',
      icon: <FiDollarSign className="w-5 h-5" />,
      content: [
        'Cancellation Policy: You may cancel your appointment up to 24 hours before the scheduled service time without any cancellation fees.',
        'Late Cancellations: Cancellations made less than 24 hours before the scheduled service may incur a cancellation fee of 25% of the service cost.',
        'No-Show Policy: If our technician arrives at your property and you are not available or refuse service, you will be charged the full service amount.',
        'Rescheduling: You may reschedule your appointment up to 2 hours before the scheduled time at no additional cost.',
        'Service Satisfaction: If you are not satisfied with our service, please contact us within 24 hours of service completion for resolution.'
      ]
    },
    {
      title: 'Refund Timeline and Process',
      icon: <FiClock className="w-5 h-5" />,
      content: [
        'Refund Eligibility: Refunds are only provided for services that have not been performed or in cases where we are unable to provide the service.',
        'Processing Time: Refunds are processed within 5-7 business days from the date of cancellation or service issue resolution.',
        'Payment Method: Refunds will be issued to the original payment method used for the transaction.',
        'Partial Refunds: In cases of partial service completion or unsatisfactory service, partial refunds may be provided based on the circumstances.',
        'Bank Processing: Please note that it may take an additional 3-5 business days for the refund to appear in your bank account, depending on your financial institution.',
        'Refund Notification: You will receive an email confirmation once your refund has been processed.'
      ]
    },
    {
      title: 'Service Guarantees and Warranties',
      icon: <FiCheckSquare className="w-5 h-5" />,
      content: [
        'Treatment Guarantee: We provide a 30-day guarantee for most pest control treatments. If pests return within this period, we will provide a follow-up treatment.',
        'Warranty Period: Extended warranties are available for certain services and can be purchased separately.',
        'Guarantee Exclusions: Our guarantee does not cover new infestations, structural issues, or pest problems caused by customer actions.',
        'Warranty Claims: To make a warranty claim, contact us within the warranty period and provide details of the issue.',
        'Service Documentation: We maintain detailed records of all treatments performed for warranty and guarantee purposes.'
      ]
    },
    {
      title: 'Changes to This Policy',
      icon: <FiRefreshCw className="w-5 h-5" />,
      content: [
        'We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.',
        'We recommend reviewing this Privacy Policy periodically for any changes.'
      ]
    }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b fixed top-0 left-0 right-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => navigate(-1)}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <FiArrowLeft className="w-5 h-5" />
              </button>
                              <div className="w-10 h-10 bg-black rounded-lg flex items-center justify-center overflow-hidden">
                <img 
                  src="/images/logo-npc.png" 
                  alt="NATIONAL PEST CONTROL Logo"
                  className="w-8 h-8 object-contain"
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                    e.currentTarget.nextElementSibling?.classList.remove('hidden');
                  }}
                />
                <FiShield className="w-6 h-6 text-white hidden" />
              </div>
              <div>
                <h1 className="text-xl font-semibold text-gray-900">Privacy Policy & Terms</h1>
                <p className="text-sm text-gray-500">Last Updated: April 11, 2025</p>
              </div>
            </div>
            
            <div className="flex items-center space-x-4">
              <div className="text-right hidden sm:block">
                <p className="text-sm font-medium text-gray-900">Welcome back,</p>
                <p className="text-sm text-gray-500">{userProfile.customer_name || user?.name || 'User'}</p>
              </div>
              <div className="w-8 h-8 bg-white rounded-full flex items-center justify-center overflow-hidden border border-gray-200">
                {userProfile.profile_pic ? (
                  <img 
                    src={`${API_BASE_URL}${userProfile.profile_pic.replace(/^\/+/, '')}`}
                    alt="Profile"
                    className="w-8 h-8 rounded-full object-cover"
                    onError={(e) => {
                      e.currentTarget.style.display = 'none';
                      e.currentTarget.nextElementSibling?.classList.remove('hidden');
                    }}
                  />
                ) : null}
                <FiUser className="w-4 h-4 text-gray-600 hidden" />
              </div>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-24">
        <div className="max-w-4xl mx-auto">
          {/* Introduction Card */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="bg-white rounded-lg shadow-sm p-8 mb-8"
          >
            <div className="flex items-center space-x-4 mb-6">
              <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                <FiShield className="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-gray-900">Your Privacy & Rights Matter</h2>
                <p className="text-gray-600">We're committed to protecting your data and ensuring transparency</p>
              </div>
            </div>
            
            <div className="space-y-4 text-gray-700">
              <p>
                At <span className="font-semibold text-black">NATIONAL PEST CONTROL</span>, we value your privacy and are committed to protecting your personal information. 
                This comprehensive policy explains how we collect, use, and safeguard your data when you use our services.
              </p>
              <p>
                By using our app and services, you agree to the terms outlined in this policy, including our Terms and Conditions, 
                Refund and Cancellation Policy, and Service Guarantees.
              </p>
            </div>
          </motion.div>

          {/* Policy Sections */}
          <div className="space-y-4">
            {sections.map((section, index) => (
              <Section
                key={section.title}
                title={section.title}
                icon={section.icon}
                content={section.content}
                isExpanded={expandedSections[section.title] || false}
                onToggle={() => toggleSection(section.title)}
                index={index}
              />
            ))}
          </div>

          {/* Contact Section */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4, duration: 0.6 }}
            className="bg-white rounded-lg shadow-sm p-8 mt-8"
          >
            <div className="flex items-center space-x-4 mb-6">
              <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                <FiGlobe className="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-gray-900">Get In Touch</h2>
                <p className="text-gray-600">Our team is here to help with any questions</p>
              </div>
            </div>
            
            <p className="text-gray-700 mb-6">
              If you have any questions about this Privacy Policy, Terms and Conditions, Refund Policy, or our data practices, please contact us:
            </p>
            
            <div className="grid md:grid-cols-2 gap-4 mb-6">
              <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                <FiMail className="w-5 h-5 text-blue-600" />
                                  <span className="text-gray-700 font-medium">info@npcpest.com</span>
              </div>
              
              <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                <FiPhone className="w-5 h-5 text-blue-600" />
                <span className="text-gray-700 font-medium">+91 8111043335</span>
              </div>
              
              <div className="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg md:col-span-2">
                <FiMapPin className="w-5 h-5 text-blue-600 mt-1" />
                <span className="text-gray-700 font-medium">
                  NATIONAL PEST CONTROL<br />
                  NO. 158, Murugan Kovil Street,<br />
                  Vanashakthi Nagar, Kolather, Chennai - 99
                </span>
              </div>
            </div>
            
            <div className="flex flex-col sm:flex-row gap-4">
              <button
                onClick={() => window.open('mailto:info@npcpest.com', '_blank')}
                className="flex-1 bg-black text-white font-semibold py-3 px-6 rounded-lg hover:bg-gray-800 transition-colors flex items-center justify-center space-x-2"
              >
                <FiMail className="w-5 h-5" />
                <span>Email Us</span>
              </button>
              
              <button
                onClick={() => window.open('tel:+918111043335', '_blank')}
                className="flex-1 bg-gray-100 text-gray-700 font-semibold py-3 px-6 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center space-x-2"
              >
                <FiPhone className="w-5 h-5" />
                <span>Call Us</span>
              </button>
            </div>
          </motion.div>

          {/* Footer Note */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.6, duration: 0.6 }}
            className="text-center mt-8"
          >
            <div className="bg-white rounded-lg shadow-sm p-6">
                              <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                                  <FiAward className="w-6 h-6 text-blue-600" />
              </div>
              <p className="text-gray-600 text-sm">
                This Privacy Policy, Terms and Conditions, and Refund Policy are effective as of <span className="font-semibold text-black">April 11, 2025</span> and will remain in effect except with respect to any changes in their provisions in the future.
              </p>
            </div>
          </motion.div>
        </div>
      </div>
    </div>
  );
};

export default PrivacyPolicy; 