import 'package:flutter/foundation.dart';
import '../models/order.dart';
import '../services/order_service.dart';

class OrderProvider with ChangeNotifier {
  final OrderService _orderService = OrderService();

  List<Order> _orders = [];
  List<Order> _filteredOrders = [];
  Order? _selectedOrder;
  bool _isLoading = false;
  String? _error;
  String _currentFilter = 'assigned';
  String _searchQuery = '';

  List<Order> get orders => _orders;
  List<Order> get filteredOrders => _filteredOrders;
  Order? get selectedOrder => _selectedOrder;
  bool get isLoading => _isLoading;
  String? get error => _error;
  String get currentFilter => _currentFilter;
  String get searchQuery => _searchQuery;

  // Computed properties
  List<Order> get assignedOrders =>
      _orders.where((order) => order.isAssigned).toList();
  List<Order> get activeOrders =>
      _orders.where((order) => order.isReached || order.isStarted).toList();
  List<Order> get completedOrders =>
      _orders.where((order) => order.isCompleted).toList();
  List<Order> get todayOrders => _orders.where((order) {
        DateTime today = DateTime.now();
        DateTime orderDate = order.serviceDate;
        return today.year == orderDate.year &&
            today.month == orderDate.month &&
            today.day == orderDate.day;
      }).toList();

  Map<String, int> get orderStats {
    Map<String, int> stats = {
      'assigned': assignedOrders.length,
      'active': activeOrders.length,
      'completed': completedOrders.length,
      'total': _orders.length,
    };
    return stats;
  }

  Future<void> loadOrders() async {
    _setLoading(true);
    _clearError();

    try {
      List<Order> orders = await _orderService.getTechnicianOrders();
      _orders = orders;
      _applyFilters();
      notifyListeners();
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  Future<void> refreshOrders() async {
    await loadOrders();
  }

  Future<bool> updateOrderStatus(String orderId, String status,
      {String? notes}) async {
    _setLoading(true);
    _clearError();

    try {
      print(
          'Debug: Updating order status - Order ID: $orderId, Status: $status');
      bool success =
          await _orderService.updateOrderStatus(orderId, status, notes: notes);
      print('Debug: Update result - Success: $success');

      if (success) {
        // Update the order in the list
        int index = _orders.indexWhere((order) => order.bookingId == orderId);
        print('Debug: Found order at index: $index');

        if (index != -1) {
          _orders[index] = _orders[index].copyWith(
            technicianStatus: status,
            technicianNotes: notes,
            updatedAt: DateTime.now(),
          );
          _applyFilters();
          notifyListeners();
          print('Debug: Order updated successfully in list');
        } else {
          print('Debug: Order not found in list, refreshing orders');
          // If order not found, refresh the entire list
          await loadOrders();
        }
      }
      return success;
    } catch (e) {
      print('Debug: Update error: $e');
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<Order?> getOrderDetails(String orderId) async {
    _setLoading(true);
    _clearError();

    try {
      Order? order = await _orderService.getOrderDetails(orderId);
      if (order != null) {
        _selectedOrder = order;
        notifyListeners();
      }
      return order;
    } catch (e) {
      _setError(e.toString());
      return null;
    } finally {
      _setLoading(false);
    }
  }

  void setFilter(String filter) {
    _currentFilter = filter;
    _applyFilters();
    notifyListeners();
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    _applyFilters();
    notifyListeners();
  }

  void clearSearch() {
    _searchQuery = '';
    _applyFilters();
    notifyListeners();
  }

  void _applyFilters() {
    List<Order> filtered = _orders;

    // Apply status filter
    switch (_currentFilter.toLowerCase()) {
      case 'assigned':
        filtered = filtered.where((order) => order.isAssigned).toList();
        break;
      case 'active':
        filtered = filtered
            .where((order) => order.isReached || order.isStarted)
            .toList();
        break;
      case 'completed':
        filtered = filtered.where((order) => order.isCompleted).toList();
        break;
      case 'today':
        filtered = todayOrders;
        break;
      default:
        // Default to assigned orders
        filtered = filtered.where((order) => order.isAssigned).toList();
        break;
    }

    // Apply search filter
    if (_searchQuery.isNotEmpty) {
      String query = _searchQuery.toLowerCase();
      filtered = filtered
          .where((order) =>
              order.customerName.toLowerCase().contains(query) ||
              order.customerPhone.contains(query) ||
              order.bookingId.toLowerCase().contains(query) ||
              order.serviceName.toLowerCase().contains(query) ||
              order.customerAddress.toLowerCase().contains(query))
          .toList();
    }

    // Sort orders by creation date (latest first)
    filtered.sort((a, b) => b.createdAt.compareTo(a.createdAt));

    _filteredOrders = filtered;
  }

  void selectOrder(Order order) {
    _selectedOrder = order;
    notifyListeners();
  }

  void clearSelectedOrder() {
    _selectedOrder = null;
    notifyListeners();
  }

  Future<bool> addOrderNotes(String orderId, String notes) async {
    _setLoading(true);
    _clearError();

    try {
      bool success = await _orderService.addOrderNotes(orderId, notes);
      if (success) {
        // Update the order in the list
        int index = _orders.indexWhere((order) => order.id == orderId);
        if (index != -1) {
          _orders[index] = _orders[index].copyWith(
            technicianNotes: notes,
            updatedAt: DateTime.now(),
          );
          _applyFilters();
          notifyListeners();
        }
      }
      return success;
    } catch (e) {
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> uploadOrderImages(
      String orderId, List<String> imagePaths) async {
    _setLoading(true);
    _clearError();

    try {
      bool success = await _orderService.uploadOrderImages(orderId, imagePaths);
      if (success) {
        // Refresh orders to get updated images
        await loadOrders();
      }
      return success;
    } catch (e) {
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  void _setLoading(bool loading) {
    _isLoading = loading;
    notifyListeners();
  }

  void _setError(String error) {
    _error = error;
    notifyListeners();
  }

  void _clearError() {
    _error = null;
    notifyListeners();
  }

  void clearError() {
    _clearError();
  }
}
