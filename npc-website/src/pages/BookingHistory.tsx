import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  FiShield, 
  FiArrowLeft, 
  FiCalendar, 
  FiClock, 
  FiMapPin, 
  FiDollarSign,
  FiCheckCircle,
  FiXCircle,
  FiClock as FiPending,
  FiStar,
  FiSearch,
  FiTag,
  FiUser,
  FiX
} from 'react-icons/fi';
import { useAuth } from '../context/AuthContext';
import { Booking, BookingsResponse } from '../types';
import { apiService } from '../services/api';
import { API_ENDPOINTS, API_BASE_URL } from '../config/api';
import { useQuery } from 'react-query';
import toast from 'react-hot-toast';

interface UserProfile {
  customer_name?: string;
  email_id?: string;
  mobile_number?: string;
  profile_pic?: string;
}

const BookingHistory: React.FC = () => {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [activeFilter, setActiveFilter] = useState<'all' | 'pending' | 'confirmed' | 'completed' | 'cancelled'>('all');
  const [selectedBooking, setSelectedBooking] = useState<Booking | null>(null);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [sortBy, setSortBy] = useState<'date' | 'amount' | 'service'>('date');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
  const [userProfile, setUserProfile] = useState<UserProfile>({});
  const [isCancelling, setIsCancelling] = useState<string | null>(null);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [bookingToCancel, setBookingToCancel] = useState<Booking | null>(null);
  const [cancellationSuccess, setCancellationSuccess] = useState(false);

  // Utility function to get image URL
  const getImageUrl = (imagePath?: string): string | null => {
    if (!imagePath) return null;
    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
      return imagePath;
    }
    // If the path already includes ServiceImages/, don't add it again
    if (imagePath.includes('ServiceImages/')) {
      return `https://npcpest.com/npc/${imagePath}`;
    }
    return `https://npcpest.com/npc/ServiceImages/${imagePath}`;
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

  // Fetch profile on component mount
  useEffect(() => {
    fetchUserProfile();
  }, []);

  // Cancel booking function
  const handleCancelBooking = async (booking: Booking) => {
    console.log('Cancel booking attempt:', {
      booking_id: booking.booking_id,
      service_date: booking.service_date,
      service_time: booking.service_time,
      can_cancel: booking.can_cancel,
      status: booking.status
    });
    
    if (!booking.can_cancel) {
      toast.error('This booking cannot be cancelled');
      return;
    }

    setBookingToCancel(booking);
    setCancellationSuccess(false);
    setShowCancelModal(true);
  };

  const confirmCancelBooking = async () => {
    if (!bookingToCancel) return;

    setIsCancelling(bookingToCancel.booking_id);
    try {
      const sessionId = localStorage.getItem('sessionId');
      console.log('Sending cancel request:', {
        booking_id: bookingToCancel.booking_id,
        session_id: sessionId,
        action: 'cancel',
        service_date: bookingToCancel.service_date,
        service_time: bookingToCancel.service_time
      });
      
      const response = await apiService.post(API_ENDPOINTS.CANCEL_BOOKING, {
        booking_id: bookingToCancel.booking_id,
        session_id: sessionId,
        action: 'cancel'
      });
      
      console.log('Cancel response:', response);

      if (response.status === 'success' || response.message === 'Booking cancelled successfully') {
        setCancellationSuccess(true);
        toast.success('Booking cancelled successfully');
        
        // Close modal and refresh after a short delay
        setTimeout(() => {
          setShowCancelModal(false);
          setBookingToCancel(null);
          setCancellationSuccess(false);
          refetch(); // Refresh bookings list
        }, 1500);
      } else {
        toast.error(response.message || 'Failed to cancel booking');
      }
    } catch (error: any) {
      console.error('Cancel booking error:', error);
      toast.error(error.message || 'Failed to cancel booking');
    } finally {
      setIsCancelling(null);
    }
  };

  // Fetch bookings
  const { data: bookingsData, isLoading, refetch } = useQuery(
    'bookings',
    () => apiService.getUserBookings(),
    {
      onSuccess: (data) => {
        // Debug: Log service images
        if (data?.bookings) {
          console.log('Bookings data received:', data.bookings);
          data.bookings.active?.forEach((booking: any) => {
            if (booking.service_image) {
              console.log(`Service: ${booking.service_name}, Image: ${booking.service_image}, URL: ${getImageUrl(booking.service_image)}`);
            }
          });
        }
      },
      retry: 1,
      onError: (error) => {
        toast.error('Failed to load bookings');
      }
    }
  );

  // Combine all bookings from different categories
  const allBookings = [
    ...(bookingsData?.bookings?.active || []),
    ...(bookingsData?.bookings?.completed || []),
    ...(bookingsData?.bookings?.cancelled || [])
  ];

  // Filter bookings based on search query and status
  const filteredBookings = allBookings.filter(booking => {
    // Status filter
    if (activeFilter !== 'all' && booking.status !== activeFilter) {
      return false;
    }
    
    // Search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      return (
        booking.service_name.toLowerCase().includes(query) ||
        booking.booking_id.toLowerCase().includes(query) ||
        booking.address.toLowerCase().includes(query) ||
        booking.service_time.toLowerCase().includes(query) ||
        booking.total_amount.toString().includes(query)
      );
    }
    
    return true;
  });

  // Sort bookings
  const sortedBookings = [...filteredBookings].sort((a, b) => {
    let comparison = 0;
    
    switch (sortBy) {
      case 'date':
        // Use created_at for date sorting to show latest bookings first
        comparison = new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
        break;
      case 'amount':
        comparison = a.total_amount - b.total_amount;
        break;
      case 'service':
        comparison = a.service_name.localeCompare(b.service_name);
        break;
    }
    
    return sortOrder === 'asc' ? comparison : -comparison;
  });

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <FiCheckCircle className="w-5 h-5 text-black" />;
      case 'accepted':
        return <FiClock className="w-5 h-5 text-blue-500" />;
      case 'pending':
        return <FiPending className="w-5 h-5 text-yellow-500" />;

      case 'cancelled':
        return <FiXCircle className="w-5 h-5 text-red-500" />;
      default:
        return <FiClock className="w-5 h-5 text-gray-500" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-100 text-green-800 border border-green-300';
      case 'accepted':
      case 'confirmed':
        return 'bg-blue-100 text-blue-800 border border-blue-300';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800 border border-yellow-300';

      case 'cancelled':
        return 'bg-red-100 text-red-800 border border-red-300';
      default:
        return 'bg-gray-100 text-gray-800 border border-gray-300';
    }
  };

  const getPaymentStatusColor = (paymentStatus: string) => {
    switch (paymentStatus) {
      case 'paid':
        return 'bg-gray-100 text-gray-700 border border-gray-300';
      case 'pending':
        return 'bg-gray-100 text-gray-700 border border-gray-300';
      case 'failed':
        return 'bg-gray-100 text-gray-700 border border-gray-300';
      case 'refunded':
        return 'bg-gray-100 text-gray-700 border border-gray-300';
      default:
        return 'bg-gray-100 text-gray-700 border border-gray-300';
    }
  };

  const getStatusIndicatorColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-500';
      case 'confirmed':
      case 'accepted':
        return 'bg-blue-500';
      case 'pending':
        return 'bg-yellow-500';
      case 'session2_pending':
        return 'bg-purple-500';
      case 'cancelled':
        return 'bg-red-500';
      default:
        return 'bg-gray-500';
    }
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const filterOptions = [
    { value: 'all', label: 'All Bookings', count: allBookings.length },
    { value: 'pending', label: 'Pending', count: allBookings.filter((b: Booking) => b.status === 'pending').length },
    { value: 'confirmed', label: 'Confirmed', count: allBookings.filter((b: Booking) => b.status === 'confirmed').length },
    { value: 'completed', label: 'Completed', count: allBookings.filter((b: Booking) => b.status === 'completed').length },

    { value: 'cancelled', label: 'Cancelled', count: allBookings.filter((b: Booking) => b.status === 'cancelled').length },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
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
                <p className="text-sm font-medium text-gray-900">Welcome,</p>
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
                <FiUser className="w-4 h-4 text-blue-600 hidden" />
              </div>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-24">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
        >
          {/* Page Header */}
          <div className="mb-6 sm:mb-8">
            <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Booking History</h2>
            <p className="text-sm sm:text-base text-gray-600">Manage and track your service bookings</p>
          </div>

          {/* Search and Filters */}
          <div className="mb-6 space-y-4">
            {/* Search Bar */}
            <div className="relative">
              <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                type="text"
                placeholder="Search bookings..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors text-sm"
              />
              {searchQuery && (
                <button
                  onClick={() => setSearchQuery('')}
                  className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              )}
            </div>

            {/* Filters Row */}
            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
              {/* Status Filter - Pill Style */}
              <div className="flex flex-col space-y-3">
                <label className="text-xs sm:text-sm font-medium text-gray-700">Filter by Status:</label>
                <div className="flex flex-wrap gap-2">
                  {filterOptions.map((option) => (
                    <button
                      key={option.value}
                      onClick={() => setActiveFilter(option.value as any)}
                      className={`px-3 py-2 rounded-full text-xs sm:text-sm font-medium transition-all duration-200 border-2 ${
                        activeFilter === option.value
                          ? 'bg-black text-white border-black shadow-md'
                          : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50 hover:border-gray-400'
                      }`}
                    >
                      <span className="flex items-center space-x-1">
                        <span>{option.label}</span>
                        <span className={`px-1.5 py-0.5 rounded-full text-xs font-bold ${
                          activeFilter === option.value
                            ? 'bg-white text-black'
                            : 'bg-gray-100 text-gray-600'
                        }`}>
                          {option.count}
                        </span>
                      </span>
                    </button>
                  ))}
                </div>
              </div>

              {/* Sort Controls */}
              <div className="flex items-center space-x-3">
                <label className="text-xs sm:text-sm font-medium text-gray-700">Sort by:</label>
                <select
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value as 'date' | 'amount' | 'service')}
                  className="border border-gray-300 rounded-lg px-2 sm:px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-black focus:border-black"
                >
                  <option value="date">Booking Date</option>
                  <option value="amount">Amount</option>
                  <option value="service">Service</option>
                </select>
              </div>
            </div>

            {/* Results Summary */}
            <div className="text-xs sm:text-sm text-gray-600">
              Showing {sortedBookings.length} of {allBookings.length} bookings
              {searchQuery && ` for "${searchQuery}"`}
            </div>
          </div>

          {/* Bookings List */}
          {isLoading ? (
            <div className="flex justify-center items-center py-12">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600"></div>
            </div>
          ) : sortedBookings.length === 0 ? (
            <div className="text-center py-12">
              <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <FiCalendar className="w-12 h-12 text-gray-400" />
              </div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">No bookings found</h3>
              <p className="text-gray-600 mb-6">
                {activeFilter === 'all' 
                  ? "You haven't made any bookings yet."
                  : `No ${activeFilter} bookings found.`
                }
              </p>
              {activeFilter === 'all' && (
                <button
                  onClick={() => navigate('/dashboard')}
                  className="btn btn-primary"
                >
                  Browse Services
                </button>
              )}
            </div>
          ) : (
            <div className="space-y-4">
              {sortedBookings.map((booking, index) => (
                <motion.div
                  key={booking.booking_id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.3, delay: index * 0.1 }}
                  className="relative bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-xl border border-gray-100 overflow-hidden hover:shadow-2xl hover:scale-[1.02] transition-all duration-300 booking-card"
                >
                  {/* Background Pattern */}
                  <div className="absolute top-0 right-0 w-40 h-40 bg-gradient-to-bl from-gray-100/40 to-transparent rounded-full -translate-y-20 translate-x-20"></div>
                  <div className="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-tr from-gray-100/30 to-transparent rounded-full translate-y-16 -translate-x-16"></div>
                  
                  <div className="relative z-10 p-3 sm:p-6">
                    {/* Header Section */}
                    <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-3 sm:mb-6 space-y-3 sm:space-y-0">
                      <div className="flex items-center space-x-3 sm:space-x-4">
                        <div className="relative">
                          <div className="w-10 h-10 sm:w-16 sm:h-16 bg-white rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg border border-gray-200 overflow-hidden">
                            {booking.service_image ? (
                              <img 
                                src={getImageUrl(booking.service_image)!} 
                                alt={booking.service_name}
                                className="w-full h-full object-cover"
                                style={{ border: '2px solid red' }} // Debug border
                                onLoad={() => {
                                  console.log('Image loaded successfully:', getImageUrl(booking.service_image));
                                }}
                                onError={(e) => {
                                  console.log('Image failed to load:', getImageUrl(booking.service_image));
                                  // Fallback to first letter if image fails to load
                                  const target = e.target as HTMLImageElement;
                                  target.style.display = 'none';
                                  target.nextElementSibling?.classList.remove('hidden');
                                }}
                              />
                            ) : null}
                            <span className={`font-bold text-base sm:text-xl ${booking.service_image ? 'hidden' : ''} text-gray-600`}>
                              {booking.service_name.charAt(0)}
                            </span>
                          </div>
                          <div className={`absolute -top-1 -right-1 sm:-top-2 sm:-right-2 w-3 h-3 sm:w-6 sm:h-6 rounded-full flex items-center justify-center shadow-md ${getStatusIndicatorColor(booking.status)}`}>
                          </div>
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center space-x-2 lg:space-x-4 mb-1 sm:mb-3">
                            <h3 className="text-sm sm:text-lg lg:text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent truncate">
                              {booking.service_name}
                            </h3>
                          </div>
                          <div className="flex flex-wrap items-start gap-1 sm:gap-2">
                            <span className={`px-2 lg:px-3 py-0.5 lg:py-1.5 rounded-full text-xs font-semibold ${getPaymentStatusColor(booking.payment_status)} truncate`}>
                              Payment: {booking.payment_status}
                            </span>
                            <span className={`px-2 lg:px-3 py-0.5 lg:py-1.5 rounded-full text-xs font-semibold ${getStatusColor(booking.status)} truncate`}>
                              Status: {booking.status_display || booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                            </span>
                            {/* Session 2 Information - on same line as status */}
                            {booking.session2_info && (
                              <span className="px-2 lg:px-3 py-0.5 lg:py-1.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-300 truncate">
                                {booking.session2_info}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                      <div className="text-right">
                        <div className="text-xl sm:text-3xl font-bold text-black">
                          ₹{booking.total_amount}
                        </div>
                        <div className="text-xs sm:text-sm text-gray-500 font-medium">Total Amount</div>
                      </div>
                    </div>

                    {/* Details Grid */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-1.5 sm:gap-3 mb-3 sm:mb-6">
                      {/* Service Date Card */}
                      <div className="group relative bg-gray-50 rounded-lg sm:rounded-xl p-1.5 sm:p-3 border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <div className="absolute top-0 right-0 w-6 h-6 sm:w-12 sm:h-12 bg-gray-200/20 rounded-full -translate-y-3 sm:-translate-y-6 translate-x-3 sm:translate-x-6"></div>
                        <div className="relative z-10">
                          <div className="flex items-center justify-between mb-1 sm:mb-2">
                            <div className="flex items-center space-x-1 sm:space-x-2">
                              <div className="w-5 h-5 sm:w-8 sm:h-8 bg-white rounded-md sm:rounded-lg flex items-center justify-center shadow-sm">
                                <FiCalendar className="w-2.5 h-2.5 sm:w-4 sm:h-4 text-blue-600" />
                              </div>
                              <span className="text-xs font-bold text-gray-600 uppercase tracking-wide">Date</span>
                            </div>
                            <div className="w-1 h-1 sm:w-2 sm:h-2 bg-gray-400/60 rounded-full group-hover:animate-pulse"></div>
                          </div>
                          <div className="text-xs sm:text-sm font-bold text-gray-900 font-mono">{formatDate(booking.service_date)}</div>
                        </div>
                      </div>
                      
                      {/* Time Slot Card */}
                      <div className="group relative bg-gray-50 rounded-lg sm:rounded-xl p-1.5 sm:p-3 border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <div className="absolute top-0 right-0 w-6 h-6 sm:w-12 sm:h-12 bg-gray-200/20 rounded-full -translate-y-3 sm:-translate-y-6 translate-x-3 sm:translate-x-6"></div>
                        <div className="relative z-10">
                          <div className="flex items-center justify-between mb-1 sm:mb-2">
                            <div className="flex items-center space-x-1 sm:space-x-2">
                              <div className="w-5 h-5 sm:w-8 sm:h-8 bg-white rounded-md sm:rounded-lg flex items-center justify-center shadow-sm">
                                <FiClock className="w-2.5 h-2.5 sm:w-4 sm:h-4 text-blue-600" />
                              </div>
                              <span className="text-xs font-bold text-gray-600 uppercase tracking-wide">Time</span>
                            </div>
                            <div className="w-1 h-1 sm:w-2 sm:h-2 bg-gray-400/60 rounded-full group-hover:animate-ping"></div>
                          </div>
                          <div className="text-xs sm:text-sm font-bold text-gray-900 font-mono">{booking.service_time}</div>
                        </div>
                      </div>
                      
                      {/* Location Card */}
                      <div className="group relative bg-gray-50 rounded-lg sm:rounded-xl p-1.5 sm:p-3 border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <div className="absolute top-0 right-0 w-6 h-6 sm:w-12 sm:h-12 bg-gray-200/20 rounded-full -translate-y-3 sm:-translate-y-6 translate-x-3 sm:translate-x-6"></div>
                        <div className="relative z-10">
                          <div className="flex items-center justify-between mb-1 sm:mb-2">
                            <div className="flex items-center space-x-1 sm:space-x-2">
                              <div className="w-5 h-5 sm:w-8 sm:h-8 bg-white rounded-md sm:rounded-lg flex items-center justify-center shadow-sm">
                                <FiMapPin className="w-2.5 h-2.5 sm:w-4 sm:h-4 text-blue-600" />
                              </div>
                              <span className="text-xs font-bold text-gray-600 uppercase tracking-wide">Location</span>
                            </div>
                            <div className="w-1 h-1 sm:w-2 sm:h-2 bg-gray-400/60 rounded-full group-hover:animate-bounce"></div>
                          </div>
                          <div className="text-xs sm:text-sm font-bold text-gray-900 truncate">{booking.address}</div>
                        </div>
                      </div>
                      
                      {/* Booking ID Card */}
                      <div className="group relative bg-gray-50 rounded-lg sm:rounded-xl p-1.5 sm:p-3 border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <div className="absolute top-0 right-0 w-6 h-6 sm:w-12 sm:h-12 bg-gray-200/20 rounded-full -translate-y-3 sm:-translate-y-6 translate-x-3 sm:translate-x-6"></div>
                        <div className="relative z-10">
                          <div className="flex items-center justify-between mb-1 sm:mb-2">
                            <div className="flex items-center space-x-1 sm:space-x-2">
                              <div className="w-5 h-5 sm:w-8 sm:h-8 bg-white rounded-md sm:rounded-lg flex items-center justify-center shadow-sm">
                                <FiShield className="w-2.5 h-2.5 sm:w-4 sm:h-4 text-blue-600" />
                              </div>
                              <span className="text-xs font-bold text-gray-600 uppercase tracking-wide">Booking ID</span>
                            </div>
                            <div className="w-1 h-1 sm:w-2 sm:h-2 bg-gray-400/60 rounded-full group-hover:animate-pulse"></div>
                          </div>
                          <div className="text-xs sm:text-sm font-bold text-gray-900 font-mono bg-white px-1 sm:px-2 py-0.5 sm:py-1 rounded border border-gray-300">
                            {booking.booking_id}
                          </div>
                        </div>
                      </div>
                    </div>

                    {booking.special_notes && (
                      <div className="mb-3 sm:mb-6 bg-gray-50 rounded-lg sm:rounded-xl p-2 sm:p-4 border border-gray-200">
                        <p className="text-xs sm:text-sm text-gray-700">
                          <strong className="text-gray-900">Special Notes:</strong> {booking.special_notes}
                        </p>
                      </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center pt-3 sm:pt-4 border-t border-gray-100 space-y-2 sm:space-y-0">
                      <div className="flex flex-row space-x-2 sm:space-x-3 w-full sm:w-auto">
                        {/* Cancel Button - Only show for cancellable bookings */}
                        {booking.can_cancel && booking.status !== 'cancelled' && booking.status !== 'completed' ? (
                          <button
                            onClick={() => handleCancelBooking(booking)}
                            disabled={isCancelling === booking.booking_id}
                            className="flex-1 sm:flex-none px-2.5 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-red-600 border-2 border-red-300 rounded-lg sm:rounded-xl hover:bg-red-50 hover:border-red-400 transition-all duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                          >
                            {isCancelling === booking.booking_id ? (
                              <div className="flex items-center justify-center">
                                <div className="animate-spin rounded-full h-3 w-3 sm:h-4 sm:w-4 border-b-2 border-red-600 mr-1 sm:mr-2"></div>
                                <span className="text-xs sm:text-sm">Cancelling...</span>
                              </div>
                            ) : (
                              <div className="flex items-center justify-center">
                                <FiX className="w-3 h-3 sm:w-4 sm:h-4 mr-1" />
                                <span className="text-xs sm:text-sm">Cancel</span>
                              </div>
                            )}
                          </button>
                        ) : null}
                        <button
                          onClick={() => navigate(`/service/${encodeURIComponent(booking.service_name)}`)}
                          className={`px-2.5 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-black border-2 border-gray-300 rounded-lg sm:rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 shadow-sm ${
                            booking.can_cancel && booking.status !== 'cancelled' && booking.status !== 'completed' ? 'flex-1 sm:flex-none' : 'w-full sm:w-auto'
                          }`}
                        >
                          Book Again
                        </button>
                      </div>
                      <button
                        onClick={() => {
                          setSelectedBooking(booking);
                          setShowDetailsModal(true);
                        }}
                        className="px-3 sm:px-6 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold bg-black text-white rounded-lg sm:rounded-xl hover:bg-gray-800 transition-all duration-200 shadow-lg hover:shadow-xl"
                      >
                        View Details
                      </button>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          )}
        </motion.div>
      </div>

      {/* Booking Details Modal */}
      {showDetailsModal && selectedBooking && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-2 sm:p-4">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[95vh] sm:max-h-[90vh] overflow-y-auto">
            <div className="p-4 sm:p-6">
              <div className="flex items-center justify-between mb-4 sm:mb-6">
                <h2 className="text-xl sm:text-2xl font-bold text-gray-900">Booking Details</h2>
                <button
                  onClick={() => setShowDetailsModal(false)}
                  className="text-gray-400 hover:text-gray-600 transition-colors p-1"
                >
                  <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div className="space-y-6">
                {/* Service Information */}
                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="text-lg font-semibold text-gray-900 mb-3">Service Information</h3>
                  
                  {/* Service Image */}
                  {selectedBooking.service_image && getImageUrl(selectedBooking.service_image) && (
                    <div className="mb-4 flex justify-center">
                      <div className="w-24 h-24 bg-white rounded-2xl flex items-center justify-center shadow-lg border border-gray-200 overflow-hidden">
                        <img 
                          src={getImageUrl(selectedBooking.service_image)!} 
                          alt={selectedBooking.service_name}
                          className="w-full h-full object-cover"
                          onError={(e) => {
                            const target = e.target as HTMLImageElement;
                            target.style.display = 'none';
                            target.nextElementSibling?.classList.remove('hidden');
                          }}
                        />
                        <span className="hidden font-bold text-xl text-gray-600">
                          {selectedBooking.service_name.charAt(0)}
                        </span>
                      </div>
                    </div>
                  )}
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Service Name</label>
                      <p className="text-sm text-gray-900">{selectedBooking.service_name}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Category</label>
                      <p className="text-sm text-gray-900">{selectedBooking.category || 'Home Service'}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Space Type</label>
                      <p className="text-sm text-gray-900">{selectedBooking.space_type || 'Standard'}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Status</label>
                      <div className="flex items-center space-x-2">
                        {getStatusIcon(selectedBooking.status)}
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(selectedBooking.status)}`}>
                          {selectedBooking.status.charAt(0).toUpperCase() + selectedBooking.status.slice(1)}
                        </span>
                        {/* Session 2 Information - on same line as status */}
                        {selectedBooking.session2_info && (
                          <span className="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-300">
                            {selectedBooking.session2_info}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Schedule Information */}
                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="text-lg font-semibold text-gray-900 mb-3">Schedule</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Service Date</label>
                      <p className="text-sm text-gray-900">{formatDate(selectedBooking.service_date)}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Time Slot</label>
                      <p className="text-sm text-gray-900">{selectedBooking.service_time}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Booking Date</label>
                      <p className="text-sm text-gray-900">{formatDate(selectedBooking.booking_date)}</p>
                    </div>
                  </div>
                </div>

                {/* Location Information */}
                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="text-lg font-semibold text-gray-900 mb-3">Location</h3>
                                       <div>
                       <label className="block text-sm font-medium text-gray-700">Service Address</label>
                       <p className="text-sm text-gray-900">{selectedBooking.address}</p>
                     </div>
                  {selectedBooking.special_notes && (
                    <div className="mt-3">
                      <label className="block text-sm font-medium text-gray-700">Special Instructions</label>
                      <p className="text-sm text-gray-900">{selectedBooking.special_notes}</p>
                    </div>
                  )}
                </div>

                {/* Payment Information */}
                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="text-lg font-semibold text-gray-900 mb-3">Payment Details</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Item Total</label>
                      <p className="text-sm text-gray-900">₹{selectedBooking.item_total}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Taxes</label>
                      <p className="text-sm text-gray-900">₹{selectedBooking.taxes}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Total Amount</label>
                      <p className="text-lg font-semibold text-gray-900">₹{selectedBooking.total_amount}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Payment Status</label>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        selectedBooking.payment_status === 'paid' 
                          ? 'bg-gray-100 text-gray-800' 
                          : 'bg-yellow-100 text-yellow-800'
                      }`}>
                        {selectedBooking.payment_status.charAt(0).toUpperCase() + selectedBooking.payment_status.slice(1)}
                      </span>
                    </div>
                    {selectedBooking.payment_mode && (
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Payment Mode</label>
                        <p className="text-sm text-gray-900">{selectedBooking.payment_mode}</p>
                      </div>
                    )}
                    {selectedBooking.payment_id && (
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Payment ID</label>
                        <p className="text-sm text-gray-900 font-mono">{selectedBooking.payment_id}</p>
                      </div>
                    )}
                  </div>
                </div>

                {/* Booking Information */}
                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="text-lg font-semibold text-gray-900 mb-3">Booking Information</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Booking ID</label>
                      <p className="text-sm text-gray-900 font-mono">{selectedBooking.booking_id}</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Can Cancel</label>
                      <p className="text-sm text-gray-900">{selectedBooking.can_cancel ? 'Yes' : 'No'}</p>
                      {!selectedBooking.can_cancel && selectedBooking.status !== 'cancelled' && selectedBooking.status !== 'completed' && (
                        <p className="text-xs text-gray-500 mt-1">
                          Service is within 3 hours of scheduled time
                        </p>
                      )}
                    </div>
                  </div>
                </div>


              </div>

              {/* Modal Actions */}
              <div className="mt-6 flex justify-end space-x-3">
                <button
                  onClick={() => setShowDetailsModal(false)}
                  className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Cancel Booking Confirmation Modal */}
      {showCancelModal && bookingToCancel && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-2 sm:p-4">
          <div className="bg-white rounded-lg max-w-md w-full mx-4">
            <div className="p-4 sm:p-6">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg sm:text-xl font-bold text-gray-900">Cancel Booking</h2>
                <button
                  onClick={() => {
                    setShowCancelModal(false);
                    setBookingToCancel(null);
                  }}
                  className="text-gray-400 hover:text-gray-600 transition-colors p-1"
                >
                  <FiX className="w-5 h-5 sm:w-6 sm:h-6" />
                </button>
              </div>

              <div className="mb-6">
                {cancellationSuccess ? (
                  <div className="flex items-center mb-4">
                    <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                      <FiCheckCircle className="w-6 h-6 text-green-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">Booking Cancelled!</h3>
                      <p className="text-sm text-gray-600">Your booking has been successfully cancelled.</p>
                    </div>
                  </div>
                ) : (
                  <div className="flex items-center mb-4">
                    <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                      <FiXCircle className="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">Are you sure?</h3>
                      <p className="text-sm text-gray-600">This action cannot be undone.</p>
                    </div>
                  </div>
                )}

                <div className="bg-gray-50 rounded-lg p-4 mb-4">
                  <h4 className="font-semibold text-gray-900 mb-2">Booking Details:</h4>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Service:</span>
                      <span className="font-medium">{bookingToCancel.service_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Date:</span>
                      <span className="font-medium">{formatDate(bookingToCancel.service_date)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Time:</span>
                      <span className="font-medium">{bookingToCancel.service_time}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Amount:</span>
                      <span className="font-medium">₹{bookingToCancel.total_amount}</span>
                    </div>
                  </div>
                </div>

                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                  <div className="flex items-start">
                    <FiClock className="w-5 h-5 text-yellow-600 mr-2 mt-0.5" />
                    <div>
                      <p className="text-sm font-medium text-yellow-800">Cancellation Policy</p>
                      <p className="text-sm text-yellow-700 mt-1">
                        Bookings can only be cancelled up to 3 hours before the scheduled service time. 
                        Cancellations made within 3 hours of service time may be subject to charges.
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {!cancellationSuccess && (
                <div className="flex justify-end space-x-3">
                  <button
                    onClick={() => {
                      setShowCancelModal(false);
                      setBookingToCancel(null);
                    }}
                    disabled={isCancelling === bookingToCancel.booking_id}
                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Keep Booking
                  </button>
                  <button
                    onClick={confirmCancelBooking}
                    disabled={isCancelling === bookingToCancel.booking_id}
                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    {isCancelling === bookingToCancel.booking_id ? (
                      <div className="flex items-center">
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                        Cancelling...
                      </div>
                    ) : (
                      'Cancel Booking'
                    )}
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default BookingHistory; 