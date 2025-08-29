import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';
import '../models/order.dart';
import 'auth_service.dart';

class OrderService {
  static final OrderService _instance = OrderService._internal();
  factory OrderService() => _instance;
  OrderService._internal();

  final AuthService _authService = AuthService();

  Future<List<Order>> getTechnicianOrders() async {
    try {
      // Ensure session is loaded
      await _authService.initialize();
      String? sessionId = _authService.sessionId;
      print('Debug: Order service - Session ID: $sessionId');
      if (sessionId == null) {
        throw Exception('Session not found');
      }

      final response = await http.get(
        Uri.parse(
            '${AppConstants.baseUrl}${AppConstants.technicianOrdersEndpoint}'),
        headers: {
          'Session-ID': sessionId,
          'Content-Type': 'application/json',
        },
      );

      print('Orders Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['status'] == 'success') {
          List<dynamic> ordersData = data['bookings'] ?? [];
          return ordersData.map((json) => Order.fromJson(json)).toList();
        } else {
          throw Exception(data['message'] ?? 'Failed to fetch orders');
        }
      } else {
        throw Exception('Network error');
      }
    } catch (e) {
      print('Get orders error: $e');
      rethrow;
    }
  }

  Future<bool> updateOrderStatus(String orderId, String status,
      {String? notes}) async {
    try {
      String? sessionId = _authService.sessionId;
      if (sessionId == null) {
        throw Exception('Session not found');
      }

      final response = await http.post(
        Uri.parse(
            '${AppConstants.baseUrl}${AppConstants.updateOrderStatusEndpoint}'),
        headers: {
          'Technician-ID': sessionId,
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'booking_id': orderId,
          'status': status,
          'notes': notes,
        }),
      );

      print('Update Status Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['status'] == 'success';
      } else {
        throw Exception('Network error');
      }
    } catch (e) {
      print('Update status error: $e');
      rethrow;
    }
  }

  Future<Order?> getOrderDetails(String orderId) async {
    // This functionality is not implemented in the backend yet
    // For now, we'll return null to avoid errors
    print('Get order details: Not implemented in backend yet');
    return null;
  }

  Future<List<Order>> getOrdersByStatus(String status) async {
    try {
      List<Order> allOrders = await getTechnicianOrders();
      return allOrders
          .where((order) =>
              order.technicianStatus.toLowerCase() == status.toLowerCase())
          .toList();
    } catch (e) {
      print('Get orders by status error: $e');
      rethrow;
    }
  }

  Future<List<Order>> getTodayOrders() async {
    try {
      List<Order> allOrders = await getTechnicianOrders();
      DateTime today = DateTime.now();
      today = DateTime(today.year, today.month, today.day);

      return allOrders.where((order) {
        DateTime orderDate = DateTime(
          order.serviceDate.year,
          order.serviceDate.month,
          order.serviceDate.day,
        );
        return orderDate.isAtSameMomentAs(today);
      }).toList();
    } catch (e) {
      print('Get today orders error: $e');
      rethrow;
    }
  }

  Future<List<Order>> searchOrders(String query) async {
    try {
      List<Order> allOrders = await getTechnicianOrders();
      query = query.toLowerCase();

      return allOrders
          .where((order) =>
              order.customerName.toLowerCase().contains(query) ||
              order.customerPhone.contains(query) ||
              order.bookingId.toLowerCase().contains(query) ||
              order.serviceName.toLowerCase().contains(query) ||
              order.customerAddress.toLowerCase().contains(query))
          .toList();
    } catch (e) {
      print('Search orders error: $e');
      rethrow;
    }
  }

  Future<Map<String, int>> getOrderStats() async {
    try {
      List<Order> allOrders = await getTechnicianOrders();

      Map<String, int> stats = {
        'assigned': 0,
        'reached': 0,
        'started': 0,
        'completed': 0,
        'cancelled': 0,
        'total': allOrders.length,
      };

      for (Order order in allOrders) {
        String status = order.technicianStatus.toLowerCase();
        if (stats.containsKey(status)) {
          stats[status] = (stats[status] ?? 0) + 1;
        }
      }

      return stats;
    } catch (e) {
      print('Get order stats error: $e');
      rethrow;
    }
  }

  // Note: Order notes and images are handled through the status update API
  // The backend doesn't have separate endpoints for these features yet
  Future<bool> addOrderNotes(String orderId, String notes) async {
    // This functionality is not implemented in the backend yet
    // For now, we'll just return success to avoid errors
    print('Add order notes: Not implemented in backend yet');
    return true;
  }

  Future<bool> uploadOrderImages(
      String orderId, List<String> imagePaths) async {
    // This functionality is not implemented in the backend yet
    // For now, we'll just return success to avoid errors
    print('Upload order images: Not implemented in backend yet');
    return true;
  }
}
