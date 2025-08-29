import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  FiShield, 
  FiArrowLeft, 
  FiCalendar, 
  FiClock, 
  FiMapPin,
  FiUser,
  FiCheckCircle,
  FiXCircle,
  FiLogOut,
  FiList,
  FiCheck,
  FiX,
  FiEye,
  FiPhone,
  FiNavigation,
  FiSearch
} from 'react-icons/fi';
import { useAuth } from '../context/AuthContext';
import { Booking, TechnicianOrdersResponse } from '../types';
import { apiService } from '../services/api';
import { API_ENDPOINTS } from '../config/api';
import { useQuery } from 'react-query';
import toast from 'react-hot-toast';

const TechnicianDashboard: React.FC = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState<'assigned' | 'active' | 'completed'>('assigned');
  const [selectedOrder, setSelectedOrder] = useState<Booking | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [dateFilter, setDateFilter] = useState('');
  const [dateRangeFilter, setDateRangeFilter] = useState('');

  // Fetch technician orders
  const { data: ordersData, isLoading, refetch } = useQuery(
    'technician-orders',
    () => apiService.getTechnicianOrders(),
    {
      retry: 1,
      onError: (error) => {
        toast.error('Failed to load orders');
      }
    }
  );

  const orders = ordersData?.bookings || [];

  // Filter orders based on search term and date filter
  const filteredOrders = (orders: Booking[]) => {
    return orders.filter(order => {
      const matchesSearch = searchTerm === '' || 
        order.customer_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        order.service_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        order.booking_id?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        order.customer_phone?.includes(searchTerm);
      
      // Date filtering logic
      let matchesDate = true;
      
      if (dateRangeFilter !== '') {
        const orderDate = new Date(order.service_date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        switch (dateRangeFilter) {
          case 'today':
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            matchesDate = orderDate >= today && orderDate < tomorrow;
            break;
          case 'tomorrow':
            const tomorrowStart = new Date(today);
            tomorrowStart.setDate(tomorrowStart.getDate() + 1);
            const dayAfterTomorrow = new Date(today);
            dayAfterTomorrow.setDate(dayAfterTomorrow.getDate() + 2);
            matchesDate = orderDate >= tomorrowStart && orderDate < dayAfterTomorrow;
            break;
          case 'this_week':
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 7);
            matchesDate = orderDate >= startOfWeek && orderDate < endOfWeek;
            break;
          case 'this_month':
            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
            matchesDate = orderDate >= startOfMonth && orderDate < endOfMonth;
            break;
          case 'next_week':
            const nextWeekStart = new Date(today);
            nextWeekStart.setDate(today.getDate() + 7 - today.getDay());
            const nextWeekEnd = new Date(nextWeekStart);
            nextWeekEnd.setDate(nextWeekStart.getDate() + 7);
            matchesDate = orderDate >= nextWeekStart && orderDate < nextWeekEnd;
            break;
          default:
            matchesDate = true;
        }
      } else if (dateFilter !== '') {
        matchesDate = order.service_date === dateFilter;
      }
      
      return matchesSearch && matchesDate;
    });
  };

  const assignedOrders = filteredOrders(orders.filter((order: Booking) => 
    order.technician_status === 'assigned'
  )).sort((a: Booking, b: Booking) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

  const activeOrders = filteredOrders(orders.filter((order: Booking) => 
    order.technician_status === 'reached' || 
    order.technician_status === 'started'
  )).sort((a: Booking, b: Booking) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

  const completedOrders = filteredOrders(orders.filter((order: Booking) => 
    order.technician_status === 'completed'
  )).sort((a: Booking, b: Booking) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

  const handleUpdateStatus = async (bookingId: string, status: string) => {
    try {
      // Check if user is logged in as technician
      const userRole = localStorage.getItem('userRole');
      const sessionId = localStorage.getItem('sessionId');
      const sessionExpiry = localStorage.getItem('sessionExpiry');
      
      console.log('User role:', userRole);
      console.log('Session ID:', sessionId);
      console.log('Session expiry:', sessionExpiry);
      console.log('Current time:', new Date().toISOString());
      console.log('Updating status:', { bookingId, status });
      
      if (userRole !== 'Technician') {
        toast.error('You must be logged in as a technician to update order status');
        return;
      }
      
      if (!sessionId) {
        toast.error('Session expired. Please login again.');
        return;
      }
      
      // Check if session has expired
      if (sessionExpiry && new Date(sessionExpiry) < new Date()) {
        toast.error('Session has expired. Please login again.');
        localStorage.removeItem('sessionId');
        localStorage.removeItem('userRole');
        localStorage.removeItem('sessionExpiry');
        return;
      }
      
      const response = await apiService.updateTechnicianStatus({
        booking_id: bookingId,
        status: status,
        notes: '' // Add empty notes field to match mobile app
      });

      console.log('Response:', response);

      if (response.status === 'success') {
        toast.success(`Order status updated to ${status}`);
        refetch();
      } else {
        // Don't show error message for failed status updates
        console.log('Status update failed:', response.message);
        // If completing service fails, still refresh to show current state
        if (status === 'completed') {
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        }
      }
    } catch (error) {
      console.error('Error updating status:', error);
      // Don't show error message for failed status updates
      
      // If completing service fails, still refresh to show current state
      if (status === 'completed') {
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      }
    }
  };

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-100 text-green-800';
      case 'started':
        return 'bg-blue-100 text-blue-800';
      case 'reached':
        return 'bg-yellow-100 text-yellow-800';
      case 'assigned':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <FiCheckCircle className="w-5 h-5 text-green-500" />;
      case 'started':
        return <FiClock className="w-5 h-5 text-blue-500" />;
      case 'reached':
        return <FiMapPin className="w-5 h-5 text-yellow-500" />;
      case 'assigned':
        return <FiList className="w-5 h-5 text-gray-500" />;
      default:
        return <FiList className="w-5 h-5 text-gray-500" />;
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

  const openGoogleMapsDirections = async (destinationAddress: string) => {
    try {
      // Encode the destination address for URL
      const encodedAddress = encodeURIComponent(destinationAddress);
      
      // Always use "Your location" (live location) as the starting point
      // Google Maps will automatically detect the user's current location
      const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=Your+location&destination=${encodedAddress}`;
      
      window.open(googleMapsUrl, '_blank');
    } catch (error) {
      console.error('Error opening Google Maps:', error);
      toast.error('Could not open Google Maps');
    }
  };

  const callCustomer = (phoneNumber: string) => {
    if (phoneNumber) {
      window.open(`tel:${phoneNumber}`, '_self');
    } else {
      toast.error('Phone number not available');
    }
  };

  const statsCards = [
    {
      title: 'Assigned Orders',
      value: assignedOrders.length,
      icon: FiList,
      color: 'bg-blue-500'
    },
    {
      title: 'Completed Today',
      value: completedOrders.filter((order: Booking) => {
        const today = new Date().toDateString();
        return new Date(order.service_date).toDateString() === today;
      }).length,
      icon: FiCheckCircle,
      color: 'bg-green-500'
    },
    {
      title: 'Total Completed',
      value: completedOrders.length,
      icon: FiCheckCircle,
      color: 'bg-purple-500'
    }
  ];

  const navigationItems = [
    {
      label: 'Assigned Orders',
      icon: FiList,
      onClick: () => setActiveTab('assigned'),
      active: activeTab === 'assigned'
    },
    {
      label: 'Active Orders',
      icon: FiClock,
      onClick: () => setActiveTab('active'),
      active: activeTab === 'active'
    },
    {
      label: 'Completed Orders',
      icon: FiCheckCircle,
      onClick: () => setActiveTab('completed'),
      active: activeTab === 'completed'
    }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Fixed Header */}
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
                <h1 className="text-xl font-semibold text-gray-900">npc Technician</h1>
                <p className="text-sm text-gray-500">Order Management</p>
              </div>
            </div>
            
            <div className="flex items-center space-x-4">
              <div className="text-right hidden sm:block">
                <p className="text-sm font-medium text-gray-900">Welcome,</p>
                <p className="text-sm text-gray-500">{user?.name || 'Technician'}</p>
              </div>
              <button
                onClick={handleLogout}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <FiLogOut className="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-24">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          {/* Sidebar Navigation */}
          <div className="lg:col-span-1">
            <nav className="bg-white rounded-lg shadow-sm p-4">
              <ul className="space-y-2">
                {navigationItems.map((item, index) => (
                  <li key={index}>
                    <button
                      onClick={item.onClick}
                      className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-left transition-colors ${
                        item.active
                          ? 'bg-gray-50 text-gray-700 border border-gray-200'
                          : 'text-gray-700 hover:bg-gray-50'
                      }`}
                    >
                      <item.icon className="w-5 h-5 flex-shrink-0" />
                      <span className="font-medium truncate">{item.label}</span>
                    </button>
                  </li>
                ))}
              </ul>
            </nav>
          </div>

          {/* Main Content */}
          <div className="lg:col-span-3">
            {/* Search and Filter Bar */}
            <div className="mb-6">
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-3 lg:gap-4">
                {/* Search Bar */}
                <div className="lg:col-span-2">
                  <div className="relative">
                    <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                    <input
                      type="text"
                      placeholder="Search orders..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="w-full pl-10 pr-4 py-2 lg:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors text-sm lg:text-base"
                    />
                  </div>
                </div>

                {/* Date Filter */}
                <div>
                  <select
                    value={dateRangeFilter}
                    onChange={(e) => setDateRangeFilter(e.target.value)}
                    className="w-full px-3 py-2 lg:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors text-sm lg:text-base"
                  >
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="tomorrow">Tomorrow</option>
                    <option value="this_week">This Week</option>
                    <option value="next_week">Next Week</option>
                    <option value="this_month">This Month</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Tab Navigation */}
            <div className="mb-6">
              <div className="border-b border-gray-200">
                <nav className="-mb-px flex space-x-2 sm:space-x-8 overflow-x-auto">
                  <button
                    onClick={() => setActiveTab('assigned')}
                    className={`py-2 px-2 sm:px-1 border-b-2 font-medium text-xs sm:text-sm transition-colors whitespace-nowrap flex-shrink-0 ${
                      activeTab === 'assigned'
                        ? 'border-black text-black'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <FiList className="inline w-4 h-4 mr-1" />
                    <span className="hidden sm:inline">Assigned Orders</span>
                    <span className="sm:hidden">Assigned</span>
                    <span className="ml-1">({assignedOrders.length})</span>
                  </button>
                  <button
                    onClick={() => setActiveTab('active')}
                    className={`py-2 px-2 sm:px-1 border-b-2 font-medium text-xs sm:text-sm transition-colors whitespace-nowrap flex-shrink-0 ${
                      activeTab === 'active'
                        ? 'border-black text-black'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <FiClock className="inline w-4 h-4 mr-1" />
                    <span className="hidden sm:inline">Active Orders</span>
                    <span className="sm:hidden">Active</span>
                    <span className="ml-1">({activeOrders.length})</span>
                  </button>
                  <button
                    onClick={() => setActiveTab('completed')}
                    className={`py-2 px-2 sm:px-1 border-b-2 font-medium text-xs sm:text-sm transition-colors whitespace-nowrap flex-shrink-0 ${
                      activeTab === 'completed'
                        ? 'border-black text-black'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <FiCheckCircle className="inline w-4 h-4 mr-1" />
                    <span className="hidden sm:inline">Completed Orders</span>
                    <span className="sm:hidden">Completed</span>
                    <span className="ml-1">({completedOrders.length})</span>
                  </button>
                </nav>
              </div>
            </div>

            {/* Orders List */}
            {isLoading ? (
              <div className="flex justify-center items-center py-12">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-black"></div>
              </div>
            ) : (
              <div className="space-y-4">
                {(activeTab === 'assigned' ? assignedOrders : activeTab === 'active' ? activeOrders : completedOrders).map((order: Booking, index: number) => (
                  <motion.div
                    key={order.booking_id}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: index * 0.1 }}
                    className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                  >
                    <div className="p-4 lg:p-6">
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                            <h3 className="text-base lg:text-lg font-semibold text-gray-900 truncate">
                              {order.customer_name}
                            </h3>
                            <div className="flex items-center space-x-2">
                              {getStatusIcon(order.technician_status || 'assigned')}
                              <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(order.technician_status || 'assigned')}`}>
                                {order.technician_status || 'assigned'}
                              </span>
                            </div>
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-4">
                            <div className="flex items-center text-xs sm:text-sm text-gray-600">
                              <FiShield className="w-4 h-4 mr-2 flex-shrink-0" />
                              <span className="truncate">{order.service_name}</span>
                            </div>
                            <div className="flex items-center text-xs sm:text-sm text-gray-600">
                              <FiCalendar className="w-4 h-4 mr-2 flex-shrink-0" />
                              <span className="truncate">{formatDate(order.service_date)}</span>
                            </div>
                            <div className="flex items-center text-xs sm:text-sm text-gray-600">
                              <FiClock className="w-4 h-4 mr-2 flex-shrink-0" />
                              <span className="truncate">{order.time_slot}</span>
                            </div>
                            <div className="flex items-center text-xs sm:text-sm text-gray-600">
                              <FiPhone className="w-4 h-4 mr-2 flex-shrink-0" />
                              <span className="truncate">{order.customer_phone}</span>
                            </div>
                          </div>

                          {order.special_notes && (
                            <div className="mb-4">
                              <p className="text-sm text-gray-600">
                                <strong>Notes:</strong> {order.special_notes}
                              </p>
                            </div>
                          )}
                        </div>
                      </div>

                      {/* Action Buttons for Assigned and Active Orders */}
                      {(activeTab === 'assigned' || activeTab === 'active') && (
                        <div className="mt-4 pt-4 border-t border-gray-200">
                          <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                            {/* Left side buttons - Primary actions */}
                            <div className="flex flex-wrap gap-2 sm:gap-3">
                              <button
                                onClick={() => setSelectedOrder(order)}
                                className="btn btn-secondary text-xs sm:text-sm flex-1 sm:flex-none"
                              >
                                <FiEye className="w-4 h-4 mr-1 sm:mr-2" />
                                <span className="hidden sm:inline">View Details</span>
                                <span className="sm:hidden">View</span>
                              </button>
                              
                              {/* Maps button - available for all active orders */}
                              {(order.technician_status === 'assigned' || 
                                order.technician_status === 'reached' || 
                                order.technician_status === 'started') && (
                                <button
                                  onClick={() => {
                                    if (order.service_address) {
                                      openGoogleMapsDirections(order.service_address);
                                    } else {
                                      toast.error('Service address not available');
                                    }
                                  }}
                                  className="btn btn-secondary text-xs sm:text-sm flex-1 sm:flex-none"
                                  title="Open Google Maps directions"
                                >
                                  <FiNavigation className="w-4 h-4 mr-1 sm:mr-2" />
                                  <span className="hidden sm:inline">Get Directions</span>
                                  <span className="sm:hidden">Directions</span>
                                </button>
                              )}

                              {/* Phone call button */}
                              <button
                                onClick={() => callCustomer(order.customer_phone || '')}
                                className="btn btn-outline text-xs sm:text-sm flex-1 sm:flex-none"
                                title="Call customer"
                              >
                                <FiPhone className="w-4 h-4 mr-1 sm:mr-2" />
                                Call
                              </button>
                            </div>
                            
                            {/* Right side buttons - Status updates */}
                            <div className="flex flex-wrap gap-2 sm:gap-3">
                              {order.technician_status === 'assigned' && (
                                <button
                                  onClick={() => handleUpdateStatus(order.booking_id, 'reached')}
                                  className="btn btn-outline text-xs sm:text-sm flex-1 sm:flex-none"
                                >
                                  <FiMapPin className="w-4 h-4 mr-1 sm:mr-2" />
                                  <span className="hidden sm:inline">Mark as Reached</span>
                                  <span className="sm:hidden">Reached</span>
                                </button>
                              )}
                              
                              {order.technician_status === 'reached' && (
                                <button
                                  onClick={() => handleUpdateStatus(order.booking_id, 'started')}
                                  className="btn btn-outline text-xs sm:text-sm flex-1 sm:flex-none"
                                >
                                  <FiClock className="w-4 h-4 mr-1 sm:mr-2" />
                                  <span className="hidden sm:inline">Start Service</span>
                          <span className="sm:hidden">Start</span>
                                </button>
                              )}
                              
                              {order.technician_status === 'started' && (
                                <button
                                  onClick={() => handleUpdateStatus(order.booking_id, 'completed')}
                                  className="btn btn-primary text-xs sm:text-sm flex-1 sm:flex-none"
                                >
                                  <FiCheck className="w-4 h-4 mr-1 sm:mr-2" />
                                  <span className="hidden sm:inline">Complete Service</span>
                                  <span className="sm:hidden">Complete</span>
                                </button>
                              )}
                            </div>
                          </div>
                        </div>
                      )}
                    </div>
                  </motion.div>
                ))}

                {(activeTab === 'assigned' ? assignedOrders : activeTab === 'active' ? activeOrders : completedOrders).length === 0 && (
                  <div className="text-center py-12">
                    <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      {activeTab === 'assigned' ? (
                        <FiList className="w-12 h-12 text-gray-400" />
                      ) : activeTab === 'active' ? (
                        <FiClock className="w-12 h-12 text-gray-400" />
                      ) : (
                        <FiCheckCircle className="w-12 h-12 text-gray-400" />
                      )}
                    </div>
                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                      No {activeTab} orders found
                    </h3>
                    <p className="text-gray-600">
                      {activeTab === 'assigned' 
                        ? "You don't have any assigned orders at the moment."
                        : activeTab === 'active'
                        ? "You don't have any active orders at the moment."
                        : "You haven't completed any orders yet."
                      }
                    </p>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Order Details Modal */}
      {selectedOrder && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-2 sm:p-4">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[95vh] sm:max-h-[90vh] overflow-y-auto">
            <div className="p-4 lg:p-6">
              <div className="flex justify-between items-center mb-4 lg:mb-6">
                <h2 className="text-lg lg:text-xl font-semibold text-gray-900">Order Details</h2>
                <button
                  onClick={() => setSelectedOrder(null)}
                  className="text-gray-400 hover:text-gray-600 p-1"
                >
                  <FiX className="w-5 h-5 lg:w-6 lg:h-6" />
                </button>
              </div>

              <div className="space-y-4 lg:space-y-6">
                {/* Customer Information */}
                <div>
                  <h3 className="text-base lg:text-lg font-medium text-gray-900 mb-2 lg:mb-3">Customer Information</h3>
                  <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Customer Name:</span>
                      <span className="font-medium">{selectedOrder.customer_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Phone:</span>
                      <span className="font-medium">{selectedOrder.customer_phone}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Email:</span>
                      <span className="font-medium">{selectedOrder.customer_email}</span>
                    </div>
                  </div>
                </div>

                {/* Service Information */}
                <div>
                  <h3 className="text-base lg:text-lg font-medium text-gray-900 mb-2 lg:mb-3">Service Information</h3>
                  <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Service Name:</span>
                      <span className="font-medium">{selectedOrder.service_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Booking ID:</span>
                      <span className="font-medium">{selectedOrder.booking_id}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Status:</span>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(selectedOrder.technician_status || 'assigned')}`}>
                        {selectedOrder.technician_status || 'assigned'}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Schedule Information */}
                <div>
                  <h3 className="text-base lg:text-lg font-medium text-gray-900 mb-2 lg:mb-3">Schedule</h3>
                  <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Service Date:</span>
                      <span className="font-medium">{selectedOrder.service_date_formatted}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Time Slot:</span>
                      <span className="font-medium">{selectedOrder.time_slot}</span>
                    </div>
                  </div>
                </div>

                {/* Address */}
                <div>
                  <h3 className="text-base lg:text-lg font-medium text-gray-900 mb-2 lg:mb-3">Service Address</h3>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <div className="flex justify-between items-start">
                      <p className="text-gray-700 whitespace-pre-line flex-1">{selectedOrder.service_address}</p>
                      <button
                        onClick={() => {
                          if (selectedOrder.service_address) {
                            openGoogleMapsDirections(selectedOrder.service_address);
                          } else {
                            toast.error('Service address not available');
                          }
                        }}
                        className="ml-4 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center"
                        title="Open Google Maps directions"
                      >
                        <FiNavigation className="w-4 h-4 mr-1" />
                        Directions
                      </button>
                    </div>
                  </div>
                </div>

                {/* Special Notes */}
                {selectedOrder.special_notes && (
                                  <div>
                  <h3 className="text-base lg:text-lg font-medium text-gray-900 mb-2 lg:mb-3">Special Notes</h3>
                    <div className="bg-gray-50 rounded-lg p-4">
                      <p className="text-gray-700">{selectedOrder.special_notes}</p>
                    </div>
                  </div>
                )}

                {/* Last Notes */}
                {selectedOrder.last_notes && (
                                  <div>
                  <h3 className="text-base lg:text-lg font-medium text-gray-900 mb-2 lg:mb-3">Last Notes</h3>
                    <div className="bg-gray-50 rounded-lg p-4">
                      <p className="text-gray-700">{selectedOrder.last_notes}</p>
                    </div>
                  </div>
                )}

                {/* Action Buttons */}
                {(activeTab === 'assigned' || activeTab === 'active') && (
                  <div className="border-t pt-4 lg:pt-6">
                    <h3 className="text-base lg:text-lg font-medium text-gray-900 mb-2 lg:mb-3">Actions</h3>
                    <div className="flex flex-wrap gap-2 lg:gap-3">
                      {/* Maps button - always available for active orders */}
                      <button
                        onClick={() => {
                          if (selectedOrder.service_address) {
                            openGoogleMapsDirections(selectedOrder.service_address);
                          } else {
                            toast.error('Service address not available');
                          }
                        }}
                        className="btn btn-secondary text-xs lg:text-sm flex-1 lg:flex-none"
                        title="Open Google Maps directions"
                      >
                        <FiNavigation className="w-4 h-4 mr-1 lg:mr-2" />
                        <span className="hidden sm:inline">Get Directions</span>
                        <span className="sm:hidden">Directions</span>
                      </button>

                      {/* Phone call button */}
                      <button
                        onClick={() => callCustomer(selectedOrder.customer_phone || '')}
                        className="btn btn-outline text-xs lg:text-sm flex-1 lg:flex-none"
                        title="Call customer"
                      >
                        <FiPhone className="w-4 h-4 mr-1 lg:mr-2" />
                        <span className="hidden sm:inline">Call Customer</span>
                        <span className="sm:hidden">Call</span>
                      </button>
                      {selectedOrder.technician_status === 'assigned' && (
                        <button
                          onClick={() => {
                            handleUpdateStatus(selectedOrder.booking_id, 'reached');
                            setSelectedOrder(null);
                          }}
                          className="btn btn-outline text-xs lg:text-sm flex-1 lg:flex-none"
                        >
                          <FiMapPin className="w-4 h-4 mr-1 lg:mr-2" />
                          <span className="hidden sm:inline">Mark as Reached</span>
                          <span className="sm:hidden">Reached</span>
                        </button>
                      )}
                      
                      {selectedOrder.technician_status === 'reached' && (
                        <button
                          onClick={() => {
                            handleUpdateStatus(selectedOrder.booking_id, 'started');
                            setSelectedOrder(null);
                          }}
                          className="btn btn-outline text-xs lg:text-sm flex-1 lg:flex-none"
                        >
                          <FiClock className="w-4 h-4 mr-1 lg:mr-2" />
                          <span className="hidden sm:inline">Start Service</span>
                          <span className="sm:hidden">Start</span>
                        </button>
                      )}
                      
                      {selectedOrder.technician_status === 'started' && (
                        <button
                          onClick={() => {
                            handleUpdateStatus(selectedOrder.booking_id, 'completed');
                            setSelectedOrder(null);
                          }}
                          className="btn btn-primary text-xs lg:text-sm flex-1 lg:flex-none"
                        >
                          <FiCheck className="w-4 h-4 mr-1 lg:mr-2" />
                          <span className="hidden sm:inline">Complete Service</span>
                          <span className="sm:hidden">Complete</span>
                        </button>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Floating Maps Button for Quick Access */}
      {(assignedOrders.length > 0 || activeOrders.length > 0) && (
        <div className="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-40">
          <button
            onClick={() => {
              // Get the first active order for quick directions
              const firstActiveOrder = assignedOrders[0] || activeOrders[0];
              if (firstActiveOrder?.service_address) {
                openGoogleMapsDirections(firstActiveOrder.service_address);
              } else {
                toast.error('No active orders with addresses found');
              }
            }}
            className="bg-blue-600 hover:bg-blue-700 text-white p-3 sm:p-4 rounded-full shadow-lg transition-all duration-200 hover:scale-110"
            title="Quick directions to next order"
          >
            <FiNavigation className="w-5 h-5 sm:w-6 sm:h-6" />
          </button>
        </div>
      )}
    </div>
  );
};

export default TechnicianDashboard; 