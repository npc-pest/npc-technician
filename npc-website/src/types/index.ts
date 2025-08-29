// User types
export interface User {
  id: string;
  email: string;
  name: string;
  mobile: string;
  role: 'User' | 'Admin' | 'Technician';
  profile_pic?: string;
}

// API Response Data types
export interface UserProfileData {
  id?: string;
  email_id?: string;
  customer_name?: string;
  employee_name?: string;
  mobile_number?: string;
  phone_number?: string;
  profile_pic?: string;
  address1?: string;
  address2?: string;
  gender?: string;
  country?: string;
}

// Specific API Response types
export interface ServicesResponse {
  status: 'success' | 'error';
  services: Service[];
  filtered_by_location?: string | null;
  total_count: number;
  message?: string;
}

export interface OffersResponse {
  status: 'success' | 'error';
  offers: Offer[];
  message?: string;
}

export interface BookingsResponse {
  user: {
    id: string;
    name: string;
    email: string;
    phone: string;
    address: string;
  };
  summary: {
    total_bookings: number;
    total_spent: number;
    active_bookings: number;
    completed_bookings: number;
  };
  bookings: {
    active: Booking[];
    completed: Booking[];
    cancelled: Booking[];
  };
}

export interface LoginResponseData {
  id?: string;
  user_id?: string; // Added for OTP login response
  email_id?: string;
  customer_name?: string;
  employee_name?: string;
  mobile_number?: string;
  phone_number?: string;
  profile_pic?: string;
}

// Service types
export interface Service {
  service_id: string;
  service_name: string;
  description: string;
  image_path?: string;
  video_path?: string;
  media_type?: 'image' | 'video' | 'both' | 'none';
  locations?: string;
  created_at?: string;
}

export interface ServiceTypeData {
  typeName: string;
  pricingFields: PricingField[];
  id?: number;
}

export interface CartItem {
  service_type_id: number;
  service_type_name: string;
  room_size: string;
  price: number;
  quantity: number;
}

export interface PricingField {
  roomSize: string;
  price: string;
  id?: number;
}

// Booking types
export interface Booking {
  booking_id: string;
  service_id?: string | null;
  service_name: string;
  service_description?: string;
  service_image?: string | null;
  cart_items?: CartItem[];
  category?: string;
  space_type?: string;
  item_total: number;
      discount?: number;
    tax?: number;
    taxes: number;
  total_amount: number;
  booking_date: string;
  service_date: string;
  service_time?: string;
  time_slot?: string;
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled';
  booking_status?: 'confirmed' | 'completed' | 'cancelled';
  address: string;
  service_address?: string;
  special_notes?: string;
  payment_mode?: string;
  payment_status: string;
  payment_id?: string | null;
  created_at: string;
  updated_at?: string;
  last_update?: string;
  last_update_formatted?: string;
  booking_date_formatted?: string;
  service_date_formatted?: string;
  created_at_formatted?: string;
  can_cancel?: boolean;
  status_color?: string;
  status_icon?: string;
  technician_status?: 'assigned' | 'reached' | 'started' | 'completed';
  customer_name?: string;
  customer_phone?: string;
  customer_email?: string;
  last_notes?: string;
  can_mark_reached?: boolean;
  can_start_work?: boolean;
  can_complete?: boolean;
  is_completed?: boolean;
  status_display?: string;
  next_action?: string;
  session2_status?: string;
  session2_date?: string;
  session2_completed_date?: string;
  session2_info?: string;
}

// Offer types
export interface Offer {
  offer_id: string;
  offer_name: string;
  coupon_number: string;
  offer_starts_on: string;
  expires_on: string;
  offer_percentage: number;
  offer_banner_location?: string;
  status?: string;
}

// Employee types
export interface Employee {
  id: string;
  name: string;
  mobile: string;
  address: string;
  email: string;
  serviceType: string;
  idProof?: string;
}

// Contact types
export interface ContactQuery {
  query_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  subject: string;
  message: string;
  status: 'pending' | 'resolved';
  created_at: string;
}

// Payment Settings types
export interface PaymentSettings {
  cash_on_delivery_enabled: boolean;
  online_payment_enabled: boolean;
}

export interface PaymentSettingsResponse {
  status: 'success' | 'error';
  payment_settings: PaymentSettings;
  message?: string;
}

// For API service compatibility
export interface PaymentSettingsApiResponse extends ApiResponse<PaymentSettingsResponse> {}

// API Response types
export interface ApiResponse<T> {
  status: 'success' | 'error';
  message?: string;
  data?: T;
  sessionid?: string;
  session_expiry?: string;
  is_new_user?: boolean;
}

export interface TechnicianOrdersResponse {
  status: 'success' | 'error';
  message?: string;
  technician: {
    technician_id: number;
    employee_name: string;
    phone_number: string;
    email: string;
  };
  bookings: Booking[];
  summary: {
    total_orders: number;
    assigned_count: number;
    active_count: number;
    completed_count: number;
  };
  grouped_bookings: {
    assigned: Booking[];
    active: Booking[];
    completed: Booking[];
  };
}

// Form types
export interface LoginForm {
  email: string;
  password: string;
  role: 'Admin' | 'Technician';
}

export interface SignupForm {
  customer_name: string;
  mobile_number: string;
  password: string;
}

export interface ContactForm {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  subject: string;
  message: string;
}

// Navigation types
export interface NavItem {
  label: string;
  path: string;
  icon: string;
  requiresAuth?: boolean;
  roles?: string[];
} 