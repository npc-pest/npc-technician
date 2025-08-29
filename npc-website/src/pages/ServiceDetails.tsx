import React, { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
// @ts-ignore
import html2canvas from 'html2canvas';
// @ts-ignore
import jsPDF from 'jspdf';
import { 
  FiShield, 
  FiArrowLeft, 
  FiCalendar, 
  FiClock, 
  FiMapPin, 
  FiCheck, 
  FiPlus, 
  FiMinus,
  FiUsers,
  FiAward,
  FiShield as FiShieldIcon,
  FiCheckCircle,
  FiAlertCircle,
  FiInfo,
  FiTruck,
  FiClock as FiTimeIcon,
  FiMapPin as FiLocationIcon,
  FiPhone,
  FiMail,
  FiCreditCard,
  FiDollarSign,
  FiPercent,
  FiGift,
  FiZap,
  FiHeart,
  FiEye,
  FiEyeOff,
  FiShare2,
  FiShoppingCart,
  FiUser,
  FiLogOut
} from 'react-icons/fi';
import { useAuth } from '../context/AuthContext';
import { Service, PaymentSettings, PaymentSettingsResponse, UserProfileData } from '../types';
import { apiService } from '../services/api';
import { API_ENDPOINTS, API_BASE_URL } from '../config/api';
import { useQuery } from 'react-query';
import toast from 'react-hot-toast';
import axios from 'axios';

// Helper function to get image URL
const getImageUrl = (imagePath?: string): string | null => {
  if (!imagePath) return null;
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://') || imagePath.startsWith('data:')) {
    return imagePath;
  }
  return `${API_BASE_URL}${imagePath.replace(/^\/+/, '')}`;
};

// Helper function to get video URL
const getVideoUrl = (videoPath?: string): string | null => {
  if (!videoPath) return null;
  if (videoPath.startsWith('http://') || videoPath.startsWith('https://') || videoPath.startsWith('data:')) {
    return videoPath;
  }
  return `${API_BASE_URL}${videoPath.replace(/^\/+/, '')}`;
};



// Local interfaces for this component
interface ServiceType {
  service_type_id: number;
  service_type_name: string;
  pricing: PricingField[];
}

interface PricingField {
  id: string;
  room_size: string;
  price: number;
}

interface CartItem {
  service_type_id: number;
  service_type_name: string;
  room_size: string;
  price: number;
  quantity: number;
}

interface BookingResponse {
  status: string;
  message?: string;
  booking_id?: string;
}

interface RazorpayOptions {
  key: string;
  amount: number;
  name: string;
  description: string;
  prefill: {
    contact?: string;
    email?: string;
  };
  external?: {
    wallets: string[];
  };
  handler: (response: RazorpayResponse) => void;
  modal?: {
    ondismiss?: () => void;
  };
}

interface RazorpayResponse {
  razorpay_payment_id: string;
  razorpay_order_id: string;
  razorpay_signature: string;
}

const ServiceDetails: React.FC = () => {
  const { serviceName } = useParams<{ serviceName: string }>();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  
  console.log('ServiceDetails component - serviceName:', serviceName);
  const [currentStep, setCurrentStep] = useState<'types' | 'details' | 'confirmation'>('types');
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [selectedTime, setSelectedTime] = useState('');
  const [address, setAddress] = useState('');
  const [notes, setNotes] = useState('');
  const [userEmail, setUserEmail] = useState('');
  const [fullName, setFullName] = useState('');
  const [isBooking, setIsBooking] = useState(false);
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [serviceTypes, setServiceTypes] = useState<ServiceType[]>([]);
  const [serviceInfo, setServiceInfo] = useState<any>(null);
  
  // New state variables for missing features
  const [couponCode, setCouponCode] = useState('');
  const [isCouponApplied, setIsCouponApplied] = useState(false);
  const [appliedCouponCode, setAppliedCouponCode] = useState('');
  const [couponDiscount, setCouponDiscount] = useState(0);
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState('cash');
  const [agreedToTerms, setAgreedToTerms] = useState(false);
  const [paymentSettings, setPaymentSettings] = useState({
    cash_on_delivery_enabled: true,
    online_payment_enabled: true
  });
  const [availablePaymentMethods, setAvailablePaymentMethods] = useState(['cash', 'online']);
  
  // Enhanced UI state
  const [showServiceDetails, setShowServiceDetails] = useState(false);
  const [selectedServiceType, setSelectedServiceType] = useState<ServiceType | null>(null);
  const [showBenefits, setShowBenefits] = useState(false);
  const [showProcess, setShowProcess] = useState(false);
  const [showTestimonials, setShowTestimonials] = useState(false);
  const [activeTab, setActiveTab] = useState<'overview' | 'details' | 'reviews'>('overview');
  
  // Profile-related state
  const [userProfile, setUserProfile] = useState<UserProfileData | null>(null);
  const [showProfileDropdown, setShowProfileDropdown] = useState(false);
  const profileDropdownRef = useRef<HTMLDivElement>(null);
  const selectedServicesRef = useRef<HTMLDivElement>(null);
  const bookingDetailsRef = useRef<HTMLDivElement>(null);
  const [showFloatingButton, setShowFloatingButton] = useState(true);

  // Fetch payment settings
  const fetchPaymentSettings = async () => {
    try {
      // Use axios directly to get the raw response
      const response = await axios.get(`${process.env.REACT_APP_API_BASE_URL || 'https://npcpest.com/npc/'}${API_ENDPOINTS.GET_PAYMENT_SETTINGS}`, {
        headers: {
          'Content-Type': 'application/json',
          'Session-ID': localStorage.getItem('sessionId')
        }
      });
      
      const data = response.data;
      if (data.status === 'success') {
        setPaymentSettings(data.payment_settings);
        
        // Update available payment methods based on settings
        const availableMethods = [];
        if (data.payment_settings.cash_on_delivery_enabled) {
          availableMethods.push('cash');
        }
        if (data.payment_settings.online_payment_enabled) {
          availableMethods.push('online');
        }
        setAvailablePaymentMethods(availableMethods);
        
        // Set default payment method to first available option
        if (availableMethods.length > 0 && !availableMethods.includes(selectedPaymentMethod)) {
          setSelectedPaymentMethod(availableMethods[0]);
        }
      }
    } catch (error: any) {
      console.error('Error fetching payment settings:', error);
      // If there's an error, default to both payment methods enabled
      setPaymentSettings({
        cash_on_delivery_enabled: true,
        online_payment_enabled: true
      });
      setAvailablePaymentMethods(['cash', 'online']);
    }
  };

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

  // Fetch profile and payment settings on component mount
  React.useEffect(() => {
    fetchUserProfile();
    fetchPaymentSettings();
  }, []);

  // Update form fields when user profile is loaded
  React.useEffect(() => {
    // Don't pre-fill the form fields - let users enter their own data
    // This ensures fresh data for each booking
  }, [userProfile, user]);

  // Handle click outside profile dropdown
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (profileDropdownRef.current && !profileDropdownRef.current.contains(event.target as Node)) {
        setShowProfileDropdown(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  // Profile dropdown handlers
  const handleProfileClick = () => {
    setShowProfileDropdown(!showProfileDropdown);
  };

  const handleProfileNavigation = () => {
    navigate('/profile');
    setShowProfileDropdown(false);
  };

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  // Fetch service types
  const { data: serviceTypesData, isLoading: typesLoading, refetch } = useQuery(
    ['serviceTypes', serviceName],
    () => apiService.get<{ status: string, data: ServiceType[], service_info: any, message: string, total_service_types: number }>(`${API_ENDPOINTS.GET_USER_SERVICE_DETAILS}?service_name=${encodeURIComponent(serviceName || '')}`),
    {
      retry: 1,
      refetchOnWindowFocus: false,
      staleTime: 0, // Always fetch fresh data
      onError: (error: any) => {
        console.error('Service types fetch error:', error);
        toast.error('Failed to load service types');
      },
      onSuccess: (data: any) => {
        console.log('API call successful:', data);
        console.log('Full API response:', JSON.stringify(data, null, 2));
      }
    }
  );

  useEffect(() => {
    console.log('ServiceTypesData received:', serviceTypesData);
    const serviceTypesDataAny = serviceTypesData as any;
    if (serviceTypesDataAny?.data) {
      console.log('Setting service types:', serviceTypesDataAny.data);
      console.log('Setting service info:', serviceTypesDataAny.service_info);
      console.log('Service warranty period:', serviceTypesDataAny.service_info?.warranty_period);
      console.log('Service image path:', serviceTypesDataAny.service_info?.image_path);
      console.log('Image URL:', getImageUrl(serviceTypesDataAny.service_info?.image_path));
      console.log('API_BASE_URL:', API_BASE_URL);
      setServiceTypes(serviceTypesDataAny.data || []);
      setServiceInfo(serviceTypesDataAny.service_info);
    }
  }, [serviceTypesData]);

  // Check if Razorpay is loaded
  useEffect(() => {
    const checkRazorpay = () => {
      if (typeof window !== 'undefined') {
        if ((window as any).Razorpay) {
          console.log('✅ Razorpay script loaded successfully');
        } else {
          console.error('❌ Razorpay script not found');
        }
      }
    };

    // Check immediately
    checkRazorpay();

    // Check again after a short delay in case script loads later
    const timer = setTimeout(checkRazorpay, 1000);
    return () => clearTimeout(timer);
  }, []);

  // Available time slots
  const timeSlots = [
    '9:00 AM - 11:00 AM',
    '11:00 AM - 1:00 PM', 
    '2:00 PM - 4:00 PM',
    '4:00 PM - 6:00 PM',
    '6:00 PM - 8:00 PM'
  ];

  // Get minimum date (today)
  const today = new Date().toISOString().split('T')[0];

  // Enhanced service information
  const serviceBenefits = [
    {
      icon: FiShieldIcon,
      title: "Professional Expertise",
      description: "Certified technicians with years of experience in all services"
    },
    {
      icon: FiAward,
      title: "Quality Guarantee",
      description: "Warranty on all services with follow-up support"
    },
    {
      icon: FiZap,
      title: "Quick Service",
      description: "Same-day service available in most areas"
    },
    {
      icon: FiHeart,
      title: "Safe Chemicals",
      description: "HACCP certified chemicals safe for your family and pets"
    }
  ];

  // Create service process steps conditionally based on has_session2
  const getServiceProcess = () => {
    // Debug: Log service info to see what data we have
    console.log('Service Info:', serviceInfo);
    console.log('has_session2 value:', serviceInfo?.has_session2);
    
    const baseSteps = [
      {
        step: 1,
        title: "Thorough Inspection",
        description: "Our expert conducts a comprehensive inspection of your space to identify all pest entry points and breeding areas.",
        icon: FiEye
      },
      {
        step: 2,
        title: "Customized Treatment",
        description: "Tailored treatment plan using advanced techniques and safe chemicals based on inspection findings.",
        icon: FiShieldIcon
      }
    ];

    // Add follow-up service step only if service has session2
    if (serviceInfo?.has_session2 === 1) {
      console.log('Adding Follow-up Service step because has_session2 = 1');
      baseSteps.push({
        step: 3,
        title: "Follow-up Service",
        description: "Second visit after 2 weeks to ensure complete elimination and prevent future infestations.",
        icon: FiCheckCircle
      });
    } else {
      console.log('Not adding Follow-up Service step. has_session2 =', serviceInfo?.has_session2);
    }

    return baseSteps;
  };

  const serviceProcess = getServiceProcess();

  const testimonials = [
    {
      name: "Sarah M.",
      rating: 5,
      comment: "Excellent service! The technician was professional and thorough. No more pests in our home.",
      date: "2 days ago"
    },
    {
      name: "Rajesh K.",
      rating: 5,
      comment: "Very satisfied with the pest control service. The team was punctual and the treatment was effective.",
      date: "1 week ago"
    },
    {
      name: "Priya S.",
      rating: 4,
      comment: "Good service and reasonable pricing. Would definitely recommend to others.",
      date: "2 weeks ago"
    }
  ];

  // Format warranty string like "5 Years Warranty" or "90 Days Warranty"
  const formatWarranty = (raw?: string) => {
    if (!raw || typeof raw !== 'string') return '90 Days Warranty';
    const s = raw.trim().toLowerCase();
    // Extract number and unit heuristically
    const numMatch = s.match(/\d+/);
    if (!numMatch) return 'Warranty';
    const num = numMatch[0];
    if (s.includes('year')) {
      return `${num} Years Warranty`;
    }
    if (s.includes('day')) {
      return `${num} Days Warranty`;
    }
    if (s.includes('month')) {
      return `${num} Months Warranty`;
    }
    // Fallback: just append Warranty
    return `${raw} Warranty`;
  };

  const addToCart = (serviceType: ServiceType, pricingField: PricingField) => {
    const cartItemKey = `${serviceType.service_type_id}_${pricingField.id}`;
    console.log('Adding to cart:', { serviceType, pricingField });
    setCartItems(prev => {
      const existing = prev.find(item => 
        item.service_type_id === serviceType.service_type_id && 
        item.room_size === pricingField.room_size
      );
      if (existing) {
        const updated = prev.map(item => 
          item.service_type_id === serviceType.service_type_id && item.room_size === pricingField.room_size
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
        console.log('Updated cart items:', updated);
        return updated;
      } else {
        const newItem = { 
          service_type_id: serviceType.service_type_id,
          service_type_name: serviceType.service_type_name,
          room_size: pricingField.room_size,
          price: pricingField.price,
          quantity: 1 
        };
        const updated = [...prev, newItem];
        console.log('Added new cart item:', newItem);
        console.log('Updated cart items:', updated);
        return updated;
      }
    });
  };

  const addAllToCart = () => {
    if (cartItems.length === 0) {
      toast.error('Please select at least one service');
      return;
    }

    // Get the global cart from localStorage
    const globalCart = JSON.parse(localStorage.getItem('globalCart') || '[]');
    
    // Add all current cart items to global cart
    const updatedGlobalCart = [...globalCart];
    
    cartItems.forEach(item => {
      const existingItem = updatedGlobalCart.find(
        globalItem => globalItem.service_type_id === item.service_type_id && globalItem.room_size === item.room_size
      );

      if (existingItem) {
        existingItem.quantity += item.quantity;
      } else {
        updatedGlobalCart.push({
          ...item,
          service_name: serviceInfo.service_name,
          service_image: serviceInfo.image_path
        });
      }
    });

    // Save to localStorage
    localStorage.setItem('globalCart', JSON.stringify(updatedGlobalCart));
    
    // Clear the current cart
    setCartItems([]);
    
    toast.success('All services added to cart!');
    navigate('/cart');
  };

  const removeFromCart = (serviceTypeId: number, roomSize: string) => {
    console.log('Removing from cart:', { serviceTypeId, roomSize });
    setCartItems(prev => {
      const existing = prev.find(item => 
        item.service_type_id === serviceTypeId && item.room_size === roomSize
      );
      if (existing && existing.quantity > 1) {
        const updated = prev.map(item => 
          item.service_type_id === serviceTypeId && item.room_size === roomSize
            ? { ...item, quantity: item.quantity - 1 }
            : item
        );
        console.log('Updated cart items (decreased quantity):', updated);
        return updated;
      } else {
        const updated = prev.filter(item => 
          !(item.service_type_id === serviceTypeId && item.room_size === roomSize)
        );
        console.log('Updated cart items (removed item):', updated);
        return updated;
      }
    });
  };

  const getCartItemQuantity = (serviceTypeId: number, roomSize: string) => {
    const item = cartItems.find(item => 
      item.service_type_id === serviceTypeId && item.room_size === roomSize
    );
    return item ? item.quantity : 0;
  };

  const calculateSubtotal = () => {
    const subtotal = cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
    console.log('Subtotal calculation:', { cartItems, subtotal });
    return subtotal;
  };

  const calculateTax = () => {
    return 1; // Fixed tax amount of ₹99
  };

  const calculateTotal = () => {
    const subtotal = calculateSubtotal();
    const tax = calculateTax();
    const total = subtotal + tax - couponDiscount;
    const finalTotal = Math.max(0, total); // Ensure total is never negative
    console.log('Total calculation:', { subtotal, tax, couponDiscount, total, finalTotal });
    return finalTotal;
  };

  const applyCoupon = async () => {
    if (!couponCode.trim()) {
      toast.error('Please enter a coupon code');
      return;
    }
    
    try {
      const orderAmount = calculateSubtotal();
      const response = await apiService.get(`${API_ENDPOINTS.CREATE_BOOKING}?action=validate_coupon&coupon_code=${encodeURIComponent(couponCode.trim())}&order_amount=${orderAmount}`);
      
      const responseData = response as any;
      
      if (responseData?.status === 'success') {
        setCouponDiscount(responseData.discount_amount);
        setAppliedCouponCode(couponCode.trim());
        setIsCouponApplied(true);
        toast.success(responseData.message || 'Coupon applied successfully!');
      } else {
        toast.error(responseData?.message || 'Invalid coupon code');
      }
    } catch (error) {
      console.error('Coupon validation error:', error);
      toast.error('Failed to validate coupon');
    }
  };

  const removeCoupon = () => {
    setCouponDiscount(0);
    setAppliedCouponCode('');
    setIsCouponApplied(false);
    setCouponCode('');
    toast.success('Coupon removed');
  };

  const handleProceedToDetails = () => {
    if (cartItems.length === 0) {
      toast.error('Please select at least one service type');
      return;
    }
    setCurrentStep('details');
    
    // On mobile, scroll to the booking details section
    setTimeout(() => {
      if (bookingDetailsRef.current && window.innerWidth < 1024) {
        bookingDetailsRef.current.scrollIntoView({ 
          behavior: 'smooth',
          block: 'start'
        });
      }
    }, 100); // Small delay to ensure the component has rendered
  };

  const scrollToSelectedServices = () => {
    if (selectedServicesRef.current) {
      selectedServicesRef.current.scrollIntoView({ 
        behavior: 'smooth',
        block: 'start'
      });
    }
  };

  // Handle scroll to hide/show floating button
  useEffect(() => {
    const handleScroll = () => {
      if (selectedServicesRef.current) {
        const rect = selectedServicesRef.current.getBoundingClientRect();
        const isVisible = rect.top <= window.innerHeight * 0.8; // Hide when 80% of section is visible
        setShowFloatingButton(!isVisible);
      }
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const handleProceedToConfirmation = () => {
    // Validate all required fields
    const missingFields = [];
    
    if (!selectedDate) missingFields.push('Date');
    if (!selectedTime) missingFields.push('Time');
    if (!fullName.trim()) missingFields.push('Full Name');
    if (!userEmail.trim()) missingFields.push('Email Address');
    if (!address.trim()) missingFields.push('Service Address');
    
    if (missingFields.length > 0) {
      toast.error(`Please fill in all required fields: ${missingFields.join(', ')}`);
      return;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(userEmail)) {
      toast.error('Please enter a valid email address');
      return;
    }
    
    if (!agreedToTerms) {
      toast.error('Please agree to terms and conditions');
      return;
    }
    setCurrentStep('confirmation');
  };

  const handleBooking = async () => {
    console.log('🔵 handleBooking called');
    console.log('🔵 Payment method:', selectedPaymentMethod);
    console.log('🔵 Agreed to terms:', agreedToTerms);
    
    if (!agreedToTerms) {
      toast.error('Please agree to the terms and conditions');
      return;
    }

    if (availablePaymentMethods.length === 0) {
      toast.error('No payment methods are currently available. Please contact support.');
      return;
    }

    if (selectedPaymentMethod === 'online') {
      console.log('🔵 Proceeding with online payment');
      initiateRazorpayPayment();
    } else {
      console.log('🔵 Proceeding with cash payment');
      createBooking();
    }
  };

  const initiateRazorpayPayment = () => {
    console.log('🔵 Initiating Razorpay payment...');
    console.log('Total amount:', calculateTotal());
    console.log('Amount in paise:', Math.round(calculateTotal() * 100));
    
    // Validate amount
    const amount = calculateTotal();
    if (amount <= 0) {
      console.error('❌ Invalid amount:', amount);
      toast.error('Invalid amount. Please select services.');
      return;
    }
    
    const amountInPaise = Math.round(amount * 100);
    if (amountInPaise <= 0) {
      console.error('❌ Invalid amount in paise:', amountInPaise);
      toast.error('Invalid amount. Please select services.');
      return;
    }
    
    // Check if Razorpay is loaded
    if (typeof window === 'undefined' || !(window as any).Razorpay) {
      console.error('❌ Razorpay script not loaded, attempting to load...');
      
      // Try to load Razorpay script dynamically
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.onload = () => {
        console.log('✅ Razorpay script loaded dynamically');
        // Retry after script loads
        setTimeout(() => {
          initiateRazorpayPayment();
        }, 100);
      };
      script.onerror = () => {
        console.error('❌ Failed to load Razorpay script');
        toast.error('Payment gateway not available. Please refresh the page.');
      };
      document.head.appendChild(script);
      return;
    }

    const options: RazorpayOptions = {
      key: 'rzp_live_R8hbCyZXj4OFJS', // Live Razorpay key
      amount: amountInPaise, // Amount in paise
      name: 'NPC Services',
      description: serviceName || 'Service Booking',
      prefill: {
        contact: '',
        email: ''
      },
      external: {
        wallets: ['paytm']
      },
      handler: (response: RazorpayResponse) => {
        console.log('✅ Payment successful:', response);
        handlePaymentSuccess(response);
      },
      modal: {
        ondismiss: () => {
          console.log('❌ Payment cancelled by user');
          toast.error('Payment cancelled');
        }
      }
    };

    console.log('🔵 Razorpay options:', options);

    try {
      const razorpay = new (window as any).Razorpay(options);
      console.log('🔵 Razorpay instance created:', razorpay);
      razorpay.open();
      console.log('🔵 Razorpay modal opened');
    } catch (error) {
      console.error('❌ Razorpay error:', error);
      toast.error('Failed to initialize payment. Please try again.');
    }
  };

  const handlePaymentSuccess = (response: RazorpayResponse) => {
    console.log('Payment successful:', response);
    createBooking(response.razorpay_payment_id, response.razorpay_order_id);
  };

  const createBooking = async (razorpayPaymentId?: string, razorpayOrderId?: string) => {
    setIsBooking(true);

    try {
      const bookingData = {
        action: 'create_booking',
        service_name: serviceName,
        service_date: selectedDate ? selectedDate.toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
        time_slot: selectedTime,
        service_address: address,
        special_notes: notes,
        user_email: userEmail,
        customer_name: fullName,
        subtotal: calculateSubtotal(),
        tax_amount: calculateTax(),
        coupon_code: isCouponApplied ? appliedCouponCode : null,
        coupon_discount: couponDiscount,
        total_amount: calculateTotal(),
        payment_method: selectedPaymentMethod,
        cart_items: cartItems,
        razorpay_order_id: razorpayOrderId
      };

      console.log('Creating booking with data:', bookingData);

      const response = await apiService.post(API_ENDPOINTS.CREATE_BOOKING, bookingData);
      const responseData = (response.data || response) as BookingResponse;

      if (responseData.status === 'success') {
        const bookingId = responseData.booking_id;
        console.log('✅ Booking created successfully with ID:', bookingId);

        // Update payment status if Razorpay payment
        if (razorpayPaymentId && bookingId) {
          await updatePaymentStatus(bookingId, razorpayPaymentId);
        }

        // Fetch user profile to get the correct name
        let userProfile = null;
        try {
          const profileResponse = await apiService.getProfile();
          if (profileResponse.status === 'success' && profileResponse.data) {
            userProfile = profileResponse.data;
          }
        } catch (error) {
          console.error('Error fetching user profile:', error);
        }

        // Create booking object for invoice
        const bookingData = {
          booking_id: bookingId,
          service_name: serviceName,
          service_date: selectedDate ? selectedDate.toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
          service_time: selectedTime,
          address: address,
          special_notes: notes,
          item_total: calculateSubtotal(),
          tax: calculateTax(), // Tax amount
          discount: couponDiscount, // Only coupon discount
          taxes: 0, // You can calculate taxes if needed
          total_amount: calculateTotal(),
          payment_status: razorpayPaymentId ? 'paid' : 'pending',
          payment_mode: selectedPaymentMethod,
          payment_id: razorpayPaymentId,
          status: 'confirmed',
          created_at: new Date().toISOString(),
          booking_date: new Date().toISOString().split('T')[0],
          cart_items: cartItems // Include cart items for sub-services
        };

        // Create invoice data with actual user profile data
        const invoiceData = {
          booking: bookingData,
          user: {
            name: fullName || userProfile?.customer_name || user?.name || 'Customer',
            email: userEmail || userProfile?.email_id || user?.email || '',
            phone: userProfile?.mobile_number || user?.mobile || '',
            address: address
          }
        };

        console.log('ServiceDetails: Created invoice data:', invoiceData);

        // Store invoice data in localStorage first
        localStorage.setItem('invoiceData', JSON.stringify(invoiceData));
        console.log('ServiceDetails: Stored invoice data in localStorage');
        
        // Also store a timestamp to ensure fresh data
        localStorage.setItem('invoiceDataTimestamp', Date.now().toString());
        
        // Show success message (backend already sends confirmation email)
        toast.success('Booking created successfully! Confirmation email has been sent to your email address.', {
          duration: 4000,
          style: {
            background: '#d1fae5',
            color: '#065f46',
            border: '1px solid #10b981'
          }
        });
        
        // Open invoice page in new tab
        const invoiceUrl = `/invoice/${bookingId}`;
        const newWindow = window.open(invoiceUrl, '_blank');
        
        if (!newWindow) {
          console.warn('Popup blocked. Invoice page could not be opened.');
          toast.error('Please allow popups to view the invoice');
        }
        
        // Redirect to bookings page
        navigate('/bookings');
      } else {
        toast.error(responseData.message || 'Booking creation failed');
      }
    } catch (error) {
      console.error('Booking error:', error);
      toast.error('Failed to create booking');
    } finally {
      setIsBooking(false);
    }
  };

  const updatePaymentStatus = async (bookingId: string, razorpayPaymentId: string) => {
    try {
      const requestBody = {
        action: 'update_payment',
        booking_id: bookingId,
        payment_status: 'paid',
        razorpay_payment_id: razorpayPaymentId
      };

      console.log('Updating payment status:', requestBody);

      const response = await apiService.post(API_ENDPOINTS.CREATE_BOOKING, requestBody);
      const responseData = response as any;

      if (responseData?.status === 'success') {
        console.log('✅ Payment status updated successfully');
      } else {
        console.log('❌ Payment update failed:', responseData?.message);
      }
    } catch (error) {
      console.error('Payment status update error:', error);
      // Don't fail the booking for payment update issues
    }
  };

  // Function to generate PDF using frontend's exact format
  const generateInvoicePDF = async (invoiceData: any): Promise<string> => {
    try {
      // Create a temporary div to render the invoice
      const tempDiv = document.createElement('div');
      tempDiv.style.position = 'absolute';
      tempDiv.style.left = '-9999px';
      tempDiv.style.top = '0';
      tempDiv.style.width = '800px';
      tempDiv.style.backgroundColor = 'white';
      tempDiv.style.padding = '40px';
      tempDiv.style.fontFamily = 'Arial, sans-serif';
      tempDiv.style.fontSize = '14px';
      tempDiv.style.lineHeight = '1.6';
      tempDiv.style.color = '#333';
      
      // Generate the invoice HTML content - matching the professional design exactly
      const invoiceHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Invoice #${invoiceData.booking.booking_id}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; background: white; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; }
                .header { margin-bottom: 40px; }
                .header-content { overflow: hidden; }
                .company-section { float: left; width: 60%; }
                .logo-container { overflow: hidden; margin-bottom: 15px; display: flex; align-items: center; }
                .logo-box { width: 50px; height: 50px; background: #0d9488; border-radius: 8px; margin-right: 15px; text-align: center; line-height: 50px; flex-shrink: 0; }
                .logo-text { font-size: 20px; font-weight: bold; color: white; }
                .company-info h1 { font-size: 24px; font-weight: bold; color: #111827; margin: 0 0 5px 0; }
                .company-info p { font-size: 14px; color: #6b7280; margin: 0 0 3px 0; }
                .invoice-title { float: right; width: 35%; text-align: right; }
                .invoice-title h2 { font-size: 32px; font-weight: bold; color: #111827; margin: 0 0 15px 0; text-transform: uppercase; white-space: nowrap; }
                .invoice-details { font-size: 14px; color: #6b7280; }
                .invoice-details p { margin: 0 0 5px 0; }
                .clear { clear: both; }
                .customer-section { margin-bottom: 40px; overflow: hidden; }
                .customer-block { float: left; width: 45%; margin-right: 5%; }
                .customer-block:last-child { margin-right: 0; }
                .customer-block h3 { font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 15px 0; border-bottom: 2px solid #0d9488; padding-bottom: 8px; }
                .customer-info p { font-size: 14px; color: #374151; margin: 0 0 5px 0; }
                .service-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
                .service-table th { background: #f9fafb; padding: 15px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #e5e7eb; }
                .service-table td { padding: 15px; font-size: 14px; border: 1px solid #e5e7eb; }
                .service-table tr:nth-child(even) { background: #f9fafb; }
                .sub-service { padding-left: 20px; }
                .payment-summary { margin-bottom: 40px; text-align: center; }
                .payment-box { width: 350px; background: #f9fafb; border: 1px solid #e5e7eb; padding: 25px; border-radius: 8px; margin: 0 auto; display: block; }
                .payment-row { margin-bottom: 12px; font-size: 14px; overflow: hidden; }
                .payment-row .label { float: left; color: #6b7280; width: 50%; }
                .payment-row .amount { float: right; color: #111827; font-weight: 500; width: 50%; text-align: right; }
                .payment-total { border-top: 2px solid #0d9488; padding-top: 15px; margin-top: 15px; font-size: 18px; font-weight: 600; overflow: hidden; }
                .payment-total .label { float: left; color: #111827; width: 50%; }
                .payment-total .amount { float: right; color: #0d9488; width: 50%; text-align: right; }
                .footer { margin-top: 50px; padding-top: 30px; border-top: 1px solid #e5e7eb; overflow: hidden; }
                .footer-section { float: left; width: 30%; margin-right: 2%; }
                .footer-section:last-child { margin-right: 0; }
                .footer-section h4 { font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 12px 0; }
                .footer-section p { font-size: 12px; color: #6b7280; margin: 0 0 5px 0; line-height: 1.4; }
            </style>
        </head>
        <body>
            <div class="container">
                <!-- Header -->
                <div class="header">
                    <div class="header-content">
                        <div class="company-section">
                            <div class="logo-container">
                                <div class="logo-box">
                                    <img src="/images/logo-npc.png" alt="NPC Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;">
                                </div>
                                <div class="company-info">
                                    <h1>npc</h1>
                                    <p>Professional Pest Control Services</p>
                                </div>
                            </div>
                            <div class="company-info">
                                <p>NPC, NO. 158, Murugan Kovil Street</p>
                                <p>Vanashakthi Nagar, Kolather, Chennai - 99</p>
                                <p>Phone: +91 8111043335</p>
                                <p>Email: info@npcpest.com</p>
                            </div>
                        </div>
                        
                        <div class="invoice-title">
                            <h2>TAX INVOICE</h2>
                            <div class="invoice-details">
                                <p><strong>Invoice #:</strong> ${invoiceData.booking.booking_id}</p>
                                <p><strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
                                <p><strong>Due Date:</strong> ${new Date().toLocaleDateString()}</p>
                            </div>
                        </div>
                    </div>
                    <div class="clear"></div>
                </div>

                <!-- Customer Information -->
                <div class="customer-section">
                    <div class="customer-block">
                        <h3>Bill To:</h3>
                        <div class="customer-info">
                            <p style="font-weight: 600;">${invoiceData.user.name}</p>
                            <p>${invoiceData.user.address}</p>
                            <p>${invoiceData.user.phone}</p>
                            <p>${invoiceData.user.email}</p>
                        </div>
                    </div>
                    
                    <div class="customer-block">
                        <h3>Service Details:</h3>
                        <div class="customer-info">
                            <p><strong>Booking Date:</strong> ${new Date(invoiceData.booking.service_date).toLocaleDateString()}</p>
                            <p><strong>Time Slot:</strong> ${invoiceData.booking.service_time}</p>
                        </div>
                    </div>
                    <div class="clear"></div>
                </div>

                <!-- Service Items Table -->
                <div style="margin-bottom: 40px;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 15px 0;">Service Details</h3>
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Main Service -->
                            <tr>
                                <td style="font-weight: 600; color: #111827; text-transform: uppercase;">${invoiceData.booking.service_name}</td>
                                <td style="color: #6b7280;">Professional pest control service</td>
                                <td style="text-align: right; color: #111827;">-</td>
                            </tr>
                            
                            <!-- Sub-services -->
                            ${invoiceData.booking.cart_items && invoiceData.booking.cart_items.length > 0 ? 
                                invoiceData.booking.cart_items.map((item: any) => `
                                    <tr style="background: #f9fafb;">
                                        <td style="padding-left: 32px; color: #111827;">• ${item.service_type_name} - ${item.room_size}</td>
                                        <td style="color: #6b7280;">Quantity: ${item.quantity} × ₹${item.price}</td>
                                        <td style="text-align: right; color: #111827;">₹${(item.price * item.quantity).toFixed(2)}</td>
                                    </tr>
                                `).join('') : 
                                `<tr style="background: #f9fafb;">
                                    <td style="padding-left: 32px; color: #111827;">• ${invoiceData.booking.service_name}</td>
                                    <td style="color: #6b7280;">Professional pest control service</td>
                                    <td style="text-align: right; color: #111827;">₹${invoiceData.booking.item_total}</td>
                                </tr>`
                            }
                        </tbody>
                    </table>
                </div>

                <!-- Payment Summary -->
                <div class="payment-summary">
                    <div class="payment-box">
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 20px 0; text-align: center;">Payment Summary</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Item Total:</td>
                                <td style="padding: 8px 0; color: #111827; font-weight: 500; font-size: 14px; text-align: right;">₹${invoiceData.booking.item_total}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Tax:</td>
                                <td style="padding: 8px 0; color: #111827; font-weight: 500; font-size: 14px; text-align: right;">₹${invoiceData.booking.tax || 99}</td>
                            </tr>
                            ${invoiceData.booking.discount > 0 ? `
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Coupon Discount:</td>
                                    <td style="padding: 8px 0; color: #10b981; font-weight: 500; font-size: 14px; text-align: right;">-₹${invoiceData.booking.discount}</td>
                                </tr>
                            ` : ''}
                            <tr style="border-top: 2px solid #0d9488;">
                                <td style="padding: 15px 0 8px 0; color: #111827; font-weight: 600; font-size: 18px;">Total Amount:</td>
                                <td style="padding: 15px 0 8px 0; color: #0d9488; font-weight: 600; font-size: 18px; text-align: right;">₹${invoiceData.booking.total_amount}</td>
                            </tr>
                            ${invoiceData.booking.payment_mode ? `
                                <tr style="border-top: 1px solid #e5e7eb;">
                                    <td style="padding: 15px 0 8px 0; color: #6b7280; font-size: 12px;">Payment Mode:</td>
                                    <td style="padding: 15px 0 8px 0; color: #6b7280; font-size: 12px; text-align: right;">${invoiceData.booking.payment_mode}</td>
                                </tr>
                            ` : ''}
                            ${invoiceData.booking.payment_id ? `
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Payment ID:</td>
                                    <td style="padding: 8px 0; color: #111827; font-weight: 500; font-size: 12px; text-align: right; font-family: monospace;">${invoiceData.booking.payment_id}</td>
                                </tr>
                            ` : ''}
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <div class="footer-section">
                        <h4>Payment Information</h4>
                        <p>Payment is due upon receipt</p>
                        <p>Late payments may incur charges</p>
                        <p>Service satisfaction guaranteed</p>
                    </div>
                    <div class="footer-section">
                        <h4>Contact Us</h4>
                        <p>Phone: +91 8111043335</p>
                        <p>Email: info@npcpest.com</p>
                        <p>Website: www.npcpest.com</p>
                    </div>
                    <div class="footer-section">
                        <h4>Terms & Conditions</h4>
                        <p>Thank you for choosing NPC Pest Control!</p>
                        <p>We appreciate your business and look forward to serving you again.</p>
                    </div>
                    <div class="clear"></div>
                </div>
            </div>
        </body>
        </html>
      `;
      
      tempDiv.innerHTML = invoiceHTML;
      document.body.appendChild(tempDiv);
      
      // Wait for content to load and render properly
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      // Generate PDF with better settings
      const canvas = await html2canvas(tempDiv, {
        background: 'white',
        width: 800,
        height: tempDiv.scrollHeight,
        useCORS: true,
        allowTaint: true
      });
      
      // Remove the temporary div
      document.body.removeChild(tempDiv);
      
      // Convert to PDF
      const imgData = canvas.toDataURL('image/png');
      const pdf = new jsPDF('p', 'mm', 'a4');
      const imgWidth = 210;
      const pageHeight = 295;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      let heightLeft = imgHeight;
      let position = 0;
      
      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;
      
      while (heightLeft >= 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
      }
      
      // Convert to base64
      const pdfBase64 = pdf.output('datauristring').split(',')[1];
      return pdfBase64;
      
    } catch (error) {
      console.error('Error generating PDF:', error);
      throw new Error('Failed to generate PDF');
    }
  };




  if (typesLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600"></div>
      </div>
    );
  }

  if (!serviceInfo || serviceTypes.length === 0) {
    console.log('Service not found condition triggered:');
    console.log('serviceInfo:', serviceInfo);
    console.log('serviceTypes:', serviceTypes);
    console.log('serviceTypes.length:', serviceTypes.length);
    
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900 mb-4">Service Not Found</h2>
          <p className="text-gray-600 mb-4">The requested service could not be found or has no available options.</p>
          <button
            onClick={() => navigate('/dashboard')}
                            className="px-6 py-3 bg-black text-white rounded-lg font-medium hover:bg-gray-800 transition-colors"
          >
            Back to Dashboard
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header - Same as User Dashboard */}
      <header className="bg-white shadow-sm border-b fixed top-0 left-0 right-0 z-50">
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
            
            <div className="flex items-center space-x-4">
              <div className="text-right hidden sm:block">
                <p className="text-sm font-medium text-gray-900">Welcome back,</p>
                <p className="text-sm text-gray-500">{userProfile?.customer_name || user?.name || 'User'}</p>
              </div>
              <div className="relative" ref={profileDropdownRef}>
                <button
                  onClick={handleProfileClick}
                  className="w-8 h-8 bg-white rounded-full flex items-center justify-center overflow-hidden hover:shadow-md transition-all duration-200 cursor-pointer border border-gray-200"
                >
                  {userProfile?.profile_pic ? (
                    <img 
                      src={`${API_BASE_URL}${userProfile?.profile_pic?.replace(/^\/+/, '')}`}
                      alt="Profile"
                      className="w-8 h-8 rounded-full object-cover"
                      onError={(e) => {
                        e.currentTarget.style.display = 'none';
                        e.currentTarget.nextElementSibling?.classList.remove('hidden');
                      }}
                    />
                  ) : null}
                  <FiUser className="w-4 h-4 text-gray-600 hidden" />
                </button>
                
                {/* Profile Dropdown */}
                {showProfileDropdown && (
                  <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                    <button
                      onClick={handleProfileNavigation}
                      className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                    >
                      <FiUser className="w-4 h-4 mr-3" />
                      Profile
                    </button>
                    <button
                      onClick={handleLogout}
                      className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                    >
                      <FiLogOut className="w-4 h-4 mr-3" />
                      Logout
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-24">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2">
            {/* Service Hero Section with Image */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="bg-white rounded-[5px] shadow-sm overflow-hidden mb-6"
            >
              <div className="relative h-80 lg:h-96 bg-gray-800 overflow-hidden">
                <div className="absolute inset-0 bg-black bg-opacity-20"></div>
                {serviceInfo.video_path && getVideoUrl(serviceInfo.video_path) ? (
                  <video
                    src={getVideoUrl(serviceInfo.video_path)!}
                    className="absolute inset-0 w-full h-full object-cover scale-[1.12]"
                    muted
                    loop
                    autoPlay
                    onError={(e) => {
                      console.log('Video failed to load:', e.currentTarget.src);
                      console.log('Original video path:', serviceInfo.video_path);
                      console.log('Constructed URL:', getVideoUrl(serviceInfo.video_path));
                      // Fallback to gradient background if video fails to load
                      e.currentTarget.style.display = 'none';
                    }}
                    onLoad={() => {
                      console.log('Video loaded successfully:', serviceInfo.video_path);
                    }}
                  />
                ) : serviceInfo.image_path && getImageUrl(serviceInfo.image_path) ? (
                  <img
                    src={getImageUrl(serviceInfo.image_path)!}
                    alt={serviceInfo.service_name}
                    className="absolute inset-0 w-full h-full object-cover"
                    onError={(e) => {
                      console.log('Image failed to load:', e.currentTarget.src);
                      console.log('Original image path:', serviceInfo.image_path);
                      console.log('Constructed URL:', getImageUrl(serviceInfo.image_path));
                      // Fallback to gradient background if image fails to load
                      e.currentTarget.style.display = 'none';
                    }}
                    onLoad={() => {
                      console.log('Image loaded successfully:', serviceInfo.image_path);
                    }}
                  />
                ) : (
                  <div className="absolute inset-0 flex items-center justify-center">
                    <FiShield className="w-16 h-16 text-white" />
                  </div>
                )}
                                  
                {/* Service Media Placeholder - only show if no media */}
                {!serviceInfo.image_path && !serviceInfo.video_path && (
                  <div className="absolute top-4 right-4 w-24 h-24 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <FiShield className="w-12 h-12 text-white" />
                  </div>
                )}
              </div>
            </motion.div>

            {/* Primary Section - Service Details */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="bg-white rounded-2xl shadow-lg border border-gray-200 mb-6"
            >
              <div className="p-4 lg:p-6">
                {/* Service Name and Image Section */}
                <div className="flex flex-col lg:flex-row lg:items-center space-y-4 lg:space-y-0">
                  <div className="flex items-center space-x-4 lg:space-x-6">
                    <div className="w-12 h-12 lg:w-16 lg:h-16 rounded-lg overflow-hidden bg-white bg-opacity-20 flex-shrink-0">
                      {serviceInfo.image_path ? (
                        <img
                          src={getImageUrl(serviceInfo.image_path)!}
                          alt={serviceInfo.service_name}
                          className="w-full h-full object-cover"
                          onError={(e) => {
                            console.log('Service card image failed to load:', e.currentTarget.src);
                            e.currentTarget.style.display = 'none';
                            e.currentTarget.nextElementSibling?.classList.remove('hidden');
                          }}
                          onLoad={() => {
                            console.log('Service card image loaded successfully:', serviceInfo.image_path);
                          }}
                        />
                      ) : serviceInfo.video_path ? (
                        <video
                          src={getVideoUrl(serviceInfo.video_path)!}
                          className="w-full h-full object-cover"
                          muted
                          loop
                          autoPlay
                          onError={(e) => {
                            console.log('Service card video failed to load:', e.currentTarget.src);
                            e.currentTarget.style.display = 'none';
                            e.currentTarget.nextElementSibling?.classList.remove('hidden');
                          }}
                          onLoad={() => {
                            console.log('Service card video loaded successfully:', serviceInfo.video_path);
                          }}
                        />
                      ) : null}
                      <div className={`w-full h-full flex items-center justify-center bg-black ${(serviceInfo.image_path || serviceInfo.video_path) ? 'hidden' : ''}`}>
                        <FiShield className="w-6 h-6 lg:w-8 lg:h-8 text-white" />
                      </div>
                    </div>
                    <div className="flex-1 min-w-0">
                      <h2 className="text-lg lg:text-xl font-bold text-gray-900 mb-2 lg:mb-3 truncate">{serviceInfo.service_name}</h2>
                      <div className="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-2 lg:space-x-6">
                        <div className="flex items-center text-xs lg:text-sm text-gray-700">
                          <FiTimeIcon className="w-3 h-3 lg:w-4 lg:h-4 mr-1 lg:mr-2 text-blue-600" />
                          <span className="truncate">1-2 hours</span>
                        </div>
                        <div className="flex items-center text-xs lg:text-sm text-gray-700">
                          <FiTruck className="w-3 h-3 lg:w-4 lg:h-4 mr-1 lg:mr-2 text-blue-600" />
                          <span className="truncate">Same day available</span>
                        </div>
                        <div className="flex items-center text-xs lg:text-sm text-gray-700">
                          <FiShieldIcon className="w-3 h-3 lg:w-4 lg:h-4 mr-1 lg:mr-2 text-blue-600" />
                          <span className="truncate">{formatWarranty(serviceInfo?.warranty_period)}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="text-center lg:text-right lg:ml-auto lg:flex lg:flex-col lg:items-end">
                    <div className="text-2xl lg:text-3xl font-bold text-gray-900">₹{serviceTypes[0]?.pricing[0]?.price || 999}</div>
                    <div className="text-xs lg:text-sm text-gray-600">Starting price</div>
                  </div>
                </div>
              </div>
            </motion.div>

            {/* Service Benefits */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="bg-white rounded-2xl shadow-sm p-6 mb-6"
            >
              <h3 className="text-xl font-bold text-gray-900 mb-4">Why Choose Our Service?</h3>
              <div className="grid grid-cols-2 gap-4">
                {serviceBenefits.map((benefit, index) => (
                  <motion.div
                    key={index}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.1 }}
                    className="p-4 bg-gray-50 rounded-xl"
                  >
                    <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0 mb-2">
                      <benefit.icon className="w-5 h-5 text-blue-600" />
                    </div>
                    <h4 className="font-semibold text-gray-900 text-sm mb-1">{benefit.title}</h4>
                    <p className="text-xs text-gray-600 leading-relaxed">{benefit.description}</p>
                  </motion.div>
                ))}
              </div>
            </motion.div>

            {/* Service Process */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="bg-white rounded-2xl shadow-sm p-6 mb-6"
            >
              <h3 className="text-xl font-bold text-gray-900 mb-4">How it works</h3>
              <div className="space-y-4">
                {serviceProcess.map((process, index) => (
                  <motion.div
                    key={index}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.1 }}
                    className="flex items-start space-x-4"
                  >
                    <div className="w-8 h-8 bg-black rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                      {process.step}
                    </div>
                    <div className="flex-1">
                      <h4 className="font-semibold text-gray-900">{process.title}</h4>
                      <p className="text-sm text-gray-600 mt-1">{process.description}</p>
                    </div>
                  </motion.div>
                ))}
              </div>
            </motion.div>

            {/* Progress Steps */}
            <div className="mb-8">
              <div className="flex items-center justify-center space-x-4">
                <div className={`flex items-center ${currentStep === 'types' ? 'text-black' : 'text-gray-400'}`}>
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center border-2 ${currentStep === 'types' ? 'border-black bg-black text-white' : 'border-gray-300'}`}>
                    1
                  </div>
                  <span className="ml-2 text-sm font-medium">Choose Services</span>
                </div>
                <div className={`w-16 h-0.5 ${currentStep === 'details' || currentStep === 'confirmation' ? 'bg-black' : 'bg-gray-300'}`}></div>
                <div className={`flex items-center ${currentStep === 'details' ? 'text-black' : currentStep === 'confirmation' ? 'text-black' : 'text-gray-400'}`}>
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center border-2 ${currentStep === 'details' || currentStep === 'confirmation' ? 'border-black bg-black text-white' : 'border-gray-300'}`}>
                    2
                  </div>
                  <span className="ml-2 text-sm font-medium">Details</span>
                </div>
                <div className={`w-16 h-0.5 ${currentStep === 'confirmation' ? 'bg-black' : 'bg-gray-300'}`}></div>
                <div className={`flex items-center ${currentStep === 'confirmation' ? 'text-black' : 'text-gray-400'}`}>
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center border-2 ${currentStep === 'confirmation' ? 'border-black bg-black text-white' : 'border-gray-300'}`}>
                    3
                  </div>
                  <span className="ml-2 text-sm font-medium">Confirm</span>
                </div>
              </div>
            </div>

            {/* Step 1: Choose Service Types */}
            {currentStep === 'types' && (
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="space-y-6"
              >
                <div className="bg-white rounded-2xl shadow-sm p-6">
                  <div className="flex items-center justify-between mb-6">
                    <h2 className="text-xl font-bold text-gray-900">Choose Your Services</h2>
                    <div className="text-sm text-gray-500">
                      {cartItems.length} item{cartItems.length !== 1 ? 's' : ''} selected
                    </div>
                  </div>
                  
                  {serviceTypes.length === 0 ? (
                    <div className="text-center py-8">
                      <FiShield className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                      <p className="text-gray-500">No service types available for this service.</p>
                    </div>
                  ) : (
                    <div className="space-y-4">
                      {serviceTypes.map((serviceType) => (
                        <motion.div
                          key={serviceType.service_type_id}
                          initial={{ opacity: 0, y: 20 }}
                          animate={{ opacity: 1, y: 0 }}
                          className="border border-gray-200 rounded-xl p-6 hover:border-teal-300 transition-colors"
                        >
                          <div className="flex items-start justify-between mb-4">
                            <div>
                              <h3 className="font-semibold text-gray-900 text-lg">{serviceType.service_type_name}</h3>
                            </div>

                          </div>
                          
                          <div className="space-y-3 sm:space-y-3 space-y-2 service-cards-container">
                            {serviceType.pricing.map((pricingField: PricingField) => (
                              <motion.div
                                key={pricingField.id}
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                className="flex items-center justify-between p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-100 service-card-mobile"
                              >
                                <div className="flex items-center space-x-2 sm:space-x-2 space-x-1 flex-1 min-w-0">
                                  <div className="w-2 h-2 bg-black rounded-full flex-shrink-0"></div>
                                  <p className="font-medium text-gray-900 text-sm sm:text-base break-words leading-tight">{pricingField.room_size}</p>
                                </div>
                                <div className="flex items-center space-x-2 sm:space-x-4 ml-2 flex-shrink-0">
                                  <div className="text-right min-w-0">
                                    <p className="text-base sm:text-lg lg:text-2xl font-bold text-black">₹{pricingField.price}</p>
                                    <p className="text-xs text-gray-500">Per Service</p>
                                  </div>
                                  <div className="flex items-center space-x-2 sm:space-x-3 ml-2">
                                    {getCartItemQuantity(serviceType.service_type_id, pricingField.room_size) > 0 && (
                                      <>
                                        <button
                                          onClick={() => removeFromCart(serviceType.service_type_id, pricingField.room_size)}
                                          className="w-10 h-10 sm:w-8 sm:h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center hover:bg-red-200 transition-colors flex-shrink-0"
                                        >
                                          <FiMinus className="w-5 h-5 sm:w-4 sm:h-4" />
                                        </button>
                                        <span className="text-base sm:text-lg font-semibold text-gray-900 w-10 sm:w-8 text-center flex-shrink-0">
                                          {getCartItemQuantity(serviceType.service_type_id, pricingField.room_size)}
                                        </span>
                                      </>
                                    )}
                                    <button
                                      onClick={() => addToCart(serviceType, pricingField)}
                                      className="w-10 h-10 sm:w-8 sm:h-8 rounded-full bg-gray-100 text-black flex items-center justify-center hover:bg-gray-200 transition-colors flex-shrink-0"
                                    >
                                      <FiPlus className="w-5 h-5 sm:w-4 sm:h-4" />
                                    </button>
                                  </div>
                                </div>
                              </motion.div>
                            ))}
                          </div>
                        </motion.div>
                      ))}
                    </div>
                  )}
                </div>
              </motion.div>
            )}

            {/* Step 2: Booking Details */}
            {currentStep === 'details' && (
              <motion.div
                ref={bookingDetailsRef}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="space-y-6"
              >
                <div className="bg-white rounded-lg shadow-sm p-6">
                  <h2 className="text-xl font-bold text-gray-900 mb-6">Booking Details</h2>
                  <p className="text-sm text-gray-600 mb-4">
                    <span className="text-red-500">*</span> Required fields
                  </p>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Date Selection */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        <FiCalendar className="inline w-4 h-4 mr-1" />
                        Select Date <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="date"
                        min={today}
                        value={selectedDate ? selectedDate.toISOString().split('T')[0] : ''}
                        onChange={(e) => setSelectedDate(e.target.value ? new Date(e.target.value) : null)}
                        required
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                      />
                    </div>

                    {/* Time Selection */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        <FiClock className="inline w-4 h-4 mr-1" />
                        Select Time <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={selectedTime}
                        onChange={(e) => setSelectedTime(e.target.value)}
                        required
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                      >
                        <option value="">Choose a time slot</option>
                        {timeSlots.map((time) => (
                          <option key={time} value={time}>{time}</option>
                        ))}
                      </select>
                    </div>

                    {/* Full Name Field */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        <FiUser className="inline w-4 h-4 mr-1" />
                        Full Name <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="text"
                        value={fullName}
                        onChange={(e) => setFullName(e.target.value)}
                        placeholder="Enter your full name"
                        required
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                      />
                    </div>

                    {/* Email Field */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        <FiMail className="inline w-4 h-4 mr-1" />
                        Email Address <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="email"
                        value={userEmail}
                        onChange={(e) => setUserEmail(e.target.value)}
                        placeholder="Enter your email address"
                        required
                        className={userEmail ? "w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" : "w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"}
                      />
                    </div>
                  </div>

                  {/* Address */}
                  <div className="mt-6">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      <FiMapPin className="inline w-4 h-4 mr-1" />
                      Service Address <span className="text-red-500">*</span>
                    </label>
                    <textarea
                      value={address}
                      onChange={(e) => setAddress(e.target.value)}
                      placeholder="Enter your complete address"
                      rows={3}
                      required
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    />
                  </div>

                  {/* Special Notes */}
                  <div className="mt-6">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Special Notes (Optional)
                    </label>
                    <textarea
                      value={notes}
                      onChange={(e) => setNotes(e.target.value)}
                      placeholder="Any special instructions or requirements"
                      rows={2}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    />
                  </div>

                  {/* Coupon Section */}
                  <div className="mt-6 coupon-section">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                      <svg className="w-5 h-5 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                      </svg>
                      Apply Coupon
                    </h3>
                    
                    {!isCouponApplied ? (
                      <div className="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                        <input
                          type="text"
                          value={couponCode}
                          onChange={(e) => setCouponCode(e.target.value)}
                          placeholder="Enter coupon code"
                          className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black"
                        />
                        <button
                          onClick={applyCoupon}
                          className="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition-colors whitespace-nowrap"
                        >
                          Apply
                        </button>
                      </div>
                    ) : (
                      <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center">
                            <svg
                              className="w-5 h-5 mr-2"
                              viewBox="0 0 24 24"
                              xmlns="http://www.w3.org/2000/svg"
                              aria-hidden="true"
                            >
                              <circle cx="12" cy="12" r="10" fill="#3B82F6" />
                              <path
                                d="M7.5 12.5l2.7 2.7 5.3-5.3"
                                fill="none"
                                stroke="#ffffff"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                              />
                            </svg>
                            <div>
                              <p className="font-medium text-blue-800">Coupon Applied: {appliedCouponCode}</p>
                              <p className="text-sm text-blue-700">You saved ₹{couponDiscount.toFixed(0)}</p>
                            </div>
                          </div>
                          <button
                            onClick={removeCoupon}
                            className="text-blue-700 hover:text-blue-900"
                          >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Payment Method */}
                  <div className="mt-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                      <svg className="w-5 h-5 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                      </svg>
                      Payment Method
                    </h3>
                    
                    <div className="space-y-3">
                      {availablePaymentMethods.length === 0 ? (
                        <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
                          <div className="flex items-center">
                            <svg className="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            <span className="text-red-800">No payment methods are currently available. Please contact support.</span>
                          </div>
                        </div>
                      ) : (
                        <>
                          {availablePaymentMethods.includes('cash') && (
                            <label className="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                              <input
                                type="radio"
                                name="paymentMethod"
                                value="cash"
                                checked={selectedPaymentMethod === 'cash'}
                                onChange={(e) => setSelectedPaymentMethod(e.target.value)}
                                className="w-4 h-4 text-black border-gray-300 focus:ring-black"
                              />
                              <div className="ml-3">
                                <p className="font-medium text-gray-900">Cash on Delivery</p>
                                <p className="text-sm text-gray-500">Pay when service is completed</p>
                              </div>
                            </label>
                          )}
                          
                          {availablePaymentMethods.includes('online') && (
                            <label className="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                              <input
                                type="radio"
                                name="paymentMethod"
                                value="online"
                                checked={selectedPaymentMethod === 'online'}
                                onChange={(e) => setSelectedPaymentMethod(e.target.value)}
                                className="w-4 h-4 text-black border-gray-300 focus:ring-black mt-1"
                              />
                              <div className="ml-3 flex-1">
                                <p className="font-medium text-gray-900">Online Payment</p>
                                <p className="text-sm text-gray-500 mb-3">Pay securely online</p>
                                
                                <div className="grid grid-cols-3 sm:grid-cols-6 gap-2 sm:gap-3">
                                  {/* Visa */}
                                  <div className="w-full h-10 bg-white border border-gray-200 rounded flex items-center justify-center shadow-sm">
                                    <img 
                                      src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/2560px-Visa_Inc._logo.svg.png" 
                                      alt="Visa" 
                                      className="w-10 h-5 sm:w-12 sm:h-6 object-contain"
                                    />
                                  </div>
                                  
                                  {/* Mastercard */}
                                  <div className="w-full h-10 bg-white border border-gray-200 rounded flex items-center justify-center shadow-sm">
                                    <img 
                                      src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" 
                                      alt="Mastercard" 
                                      className="w-10 h-5 sm:w-12 sm:h-6 object-contain"
                                    />
                                  </div>
                                  
                                  {/* Google Pay */}
                                  <div className="w-full h-10 bg-white border border-gray-200 rounded flex items-center justify-center shadow-sm">
                                    <img 
                                      src="https://developers.google.com/static/identity/images/g-logo.png" 
                                      alt="Google Pay" 
                                      className="w-10 h-5 sm:w-12 sm:h-6 object-contain"
                                      onError={(e) => {
                                        const target = e.currentTarget as HTMLImageElement;
                                        target.style.display = 'none';
                                        const fallback = target.nextElementSibling as HTMLElement;
                                        if (fallback) fallback.style.display = 'flex';
                                      }}
                                    />
                                    <div className="w-10 h-5 sm:w-12 sm:h-6 bg-white rounded flex items-center justify-center" style={{display: 'none'}}>
                                      <span className="text-gray-700 text-xs font-bold">Google Pay</span>
                                    </div>
                                  </div>
                                  
                                  {/* PhonePe */}
                                  <div className="w-full h-10 bg-white border border-gray-200 rounded flex items-center justify-center shadow-sm">
                                    <div className="w-10 h-5 sm:w-12 sm:h-6 bg-purple-600 rounded flex items-center justify-center">
                                      <span className="text-white text-sm sm:text-base font-bold leading-none">पे</span>
                                    </div>
                                  </div>
                                  
                                  {/* Paytm */}
                                  <div className="w-full h-10 bg-white border border-gray-200 rounded flex items-center justify-center shadow-sm">
                                    <img 
                                      src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Paytm_Logo_%28standalone%29.svg/1200px-Paytm_Logo_%28standalone%29.svg.png" 
                                      alt="Paytm" 
                                      className="w-10 h-5 sm:w-12 sm:h-6 object-contain"
                                    />
                                  </div>
                                  
                                  {/* UPI */}
                                  <div className="w-full h-10 bg-white border border-gray-200 rounded flex items-center justify-center shadow-sm">
                                    <img 
                                      src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/UPI-Logo-vector.svg/1200px-UPI-Logo-vector.svg.png" 
                                      alt="UPI" 
                                      className="w-10 h-5 sm:w-12 sm:h-6 object-contain"
                                    />
                                  </div>
                                </div>
                              </div>
                            </label>
                          )}
                        </>
                      )}
                    </div>
                  </div>

                  {/* Terms and Conditions */}
                  <div className="mt-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                      <svg className="w-5 h-5 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Terms & Conditions
                    </h3>
                    
                    <div className="p-4 bg-gray-50 border border-gray-200 rounded-lg mb-4">
                      <ul className="space-y-2 text-sm text-gray-600">
                        <li>• Service will be provided on the scheduled date and time</li>
                        <li>• Payment is due upon completion of service</li>
                        <li>• Cancellation must be made 3 hours in advance</li>
                        <li>• Additional charges may apply for extra requirements</li>
                        <li>• Service guarantee as per company policy</li>
                      </ul>
                    </div>
                    
                    <label className="flex items-start cursor-pointer">
                      <input
                        type="checkbox"
                        checked={agreedToTerms}
                        onChange={(e) => setAgreedToTerms(e.target.checked)}
                        className="w-4 h-4 text-black border-gray-300 rounded focus:ring-black mt-1"
                      />
                      <span className="ml-3 text-sm text-gray-700">
                        I agree to the terms and conditions and privacy policy
                      </span>
                    </label>
                  </div>
                </div>
              </motion.div>
            )}

            {/* Step 3: Confirmation */}
            {currentStep === 'confirmation' && (
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="space-y-6"
              >
                <div className="bg-white rounded-lg shadow-sm p-6">
                  <h2 className="text-xl font-bold text-gray-900 mb-6">Booking Summary</h2>
                  
                  <div className="space-y-4">
                    <div className="border-b pb-4">
                      <h3 className="font-semibold text-gray-900 mb-2">Selected Services</h3>
                      {cartItems.map((item) => (
                        <div key={`${item.service_type_id}_${item.room_size}`} className="flex justify-between items-start py-1">
                          <span className="text-gray-700 break-words flex-1">{item.service_type_name} ({item.room_size}) x {item.quantity}</span>
                          <span className="font-semibold ml-2 flex-shrink-0">₹{item.price * item.quantity}</span>
                        </div>
                      ))}
                    </div>

                    <div className="border-b pb-4">
                      <h3 className="font-semibold text-gray-900 mb-2">Booking Details</h3>
                      <div className="space-y-2 text-sm">
                        <div className="flex justify-between">
                          <span className="text-gray-600">Name:</span>
                          <span className="text-gray-900">{fullName}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-600">Date:</span>
                          <span className="text-gray-900">{selectedDate ? selectedDate.toLocaleDateString() : 'Not selected'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-600">Time:</span>
                          <span className="text-gray-900">{selectedTime}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-600">Email:</span>
                          <span className="text-gray-900">{userEmail}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-600">Address:</span>
                          <span className="text-gray-900">{address}</span>
                        </div>
                        {notes && (
                          <div className="flex justify-between">
                            <span className="text-gray-600">Notes:</span>
                            <span className="text-gray-900">{notes}</span>
                          </div>
                        )}
                      </div>
                    </div>

                    <div className="space-y-2">
                      <div className="flex justify-between">
                        <span className="text-gray-600">Subtotal:</span>
                        <span className="font-semibold">₹{calculateSubtotal()}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Tax:</span>
                        <span className="font-semibold text-gray-600">₹{calculateTax()}</span>
                      </div>
                      {isCouponApplied && (
                        <div className="flex justify-between">
                          <span className="text-gray-600">Coupon Discount ({appliedCouponCode}):</span>
                          <span className="font-semibold text-black">-₹{couponDiscount.toFixed(0)}</span>
                        </div>
                      )}
                      <div className="flex justify-between text-lg font-bold text-black border-t pt-2">
                        <span>Total Amount:</span>
                        <span>₹{calculateTotal()}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </motion.div>
            )}
          </div>

          {/* Sticky Sidebar */}
          <div className="lg:col-span-1">
            <div className="sticky top-24">
              {/* Cart Summary */}
              <motion.div
                ref={selectedServicesRef}
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                className="bg-white rounded-2xl shadow-lg p-6 mb-6"
              >
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-semibold text-gray-900">Selected Services</h3>
                  <div className="text-sm text-black font-medium">
                    {cartItems.length} item{cartItems.length !== 1 ? 's' : ''}
                  </div>
                </div>
                
                {cartItems.length > 0 ? (
                  <>
                    <div className="space-y-3 mb-4">
                      {cartItems.map((item) => (
                        <div key={`${item.service_type_id}_${item.room_size}`} className="flex justify-between items-start p-3 bg-gray-50 rounded-lg">
                          <div className="flex-1 min-w-0">
                            <span className="font-medium text-gray-900 text-sm break-words">{item.service_type_name}</span>
                            <p className="text-xs text-gray-600 break-words leading-tight">{item.room_size} x {item.quantity}</p>
                          </div>
                          <span className="font-semibold text-black ml-2 flex-shrink-0">₹{item.price * item.quantity}</span>
                        </div>
                      ))}
                    </div>
                    
                    <div className="border-t pt-4 space-y-2">
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Subtotal:</span>
                        <span className="font-semibold">₹{calculateSubtotal()}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Tax:</span>
                        <span className="font-semibold text-gray-600">₹{calculateTax()}</span>
                      </div>
                      {isCouponApplied && (
                        <div className="flex justify-between items-center">
                          <span className="text-gray-600">Coupon:</span>
                          <span className="font-semibold text-black">-₹{couponDiscount.toFixed(0)}</span>
                        </div>
                      )}
                      <div className="flex justify-between items-center pt-2 border-t">
                        <span className="text-lg font-bold text-gray-900">Total:</span>
                        <span className="text-xl font-bold text-black">₹{calculateTotal()}</span>
                      </div>
                    </div>
                  </>
                                 ) : (
                   <div className="text-center py-8">
                     <div className="text-gray-400">
                       <FiShoppingCart className="w-12 h-12 mx-auto" />
                     </div>
                   </div>
                 )}
              </motion.div>

              {/* Action Buttons */}
              {currentStep === 'types' && cartItems.length > 0 && (
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="bg-white rounded-2xl shadow-lg p-6 space-y-3"
                >
                  <button
                    onClick={handleProceedToDetails}
                    className="w-full px-6 py-4 bg-black text-white rounded-xl font-semibold hover:bg-gray-800 transition-all duration-200 transform hover:scale-105 shadow-lg"
                  >
                    Continue to Details
                  </button>
                </motion.div>
              )}

              {currentStep === 'details' && (
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="bg-white rounded-2xl shadow-lg p-6"
                >
                  <div className="space-y-4">
                    <button
                      onClick={() => setCurrentStep('types')}
                      className="w-full px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors"
                    >
                      Back to Services
                    </button>
                    <button
                      onClick={handleProceedToConfirmation}
                      className="w-full px-6 py-4 bg-black text-white rounded-xl font-semibold hover:bg-gray-800 transition-all duration-200 transform hover:scale-105 shadow-lg"
                    >
                      Review & Confirm
                    </button>
                  </div>
                </motion.div>
              )}

              {currentStep === 'confirmation' && (
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="bg-white rounded-2xl shadow-lg p-6"
                >
                  <div className="space-y-4">
                    <button
                      onClick={() => setCurrentStep('details')}
                      className="w-full px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors"
                    >
                      Back to Details
                    </button>
                    <button
                      onClick={handleBooking}
                      disabled={isBooking}
                      className="w-full px-6 py-4 bg-black text-white rounded-xl font-semibold hover:bg-gray-800 disabled:bg-gray-300 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105 shadow-lg"
                    >
                      {isBooking ? (
                        <div className="flex items-center justify-center">
                          <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                          Creating Booking...
                        </div>
                      ) : (
                        `Confirm Booking - ₹${calculateTotal()}`
                      )}
                    </button>
                  </div>
                </motion.div>
              )}


            </div>
          </div>
        </div>
      </div>

      {/* Floating Continue Button - Mobile Only */}
      <AnimatePresence>
        {cartItems.length > 0 && currentStep === 'types' && showFloatingButton && (
          <motion.div
            initial={{ opacity: 0, y: 100 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 100 }}
            className="fixed bottom-6 left-4 right-4 z-50 floating-continue-btn lg:hidden"
          >
            <button
              onClick={scrollToSelectedServices}
              className="w-full bg-black text-white px-6 py-4 rounded-[5px] font-semibold shadow-lg hover:bg-gray-800 transition-all duration-200 transform hover:scale-105 flex items-center justify-center space-x-2"
            >
              <span>Continue</span>
              <FiArrowLeft className="w-4 h-4 rotate-180" />
            </button>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default ServiceDetails; 