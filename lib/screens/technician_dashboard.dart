import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:intl/intl.dart';
import 'package:flutter/services.dart';
import '../providers/technician_provider.dart';
import '../providers/order_provider.dart';
import '../services/session_monitor_service.dart';
import '../services/auth_service.dart';
import '../utils/theme.dart';
import '../utils/constants.dart';
import '../widgets/order_card.dart';
import '../widgets/stats_card.dart';
import '../widgets/search_bar.dart';
import '../screens/order_details_screen.dart';

class TechnicianDashboard extends StatefulWidget {
  const TechnicianDashboard({super.key});

  @override
  State<TechnicianDashboard> createState() => _TechnicianDashboardState();
}

class _TechnicianDashboardState extends State<TechnicianDashboard>
    with WidgetsBindingObserver {
  int _currentIndex = 0;
  final TextEditingController _searchController = TextEditingController();
  final SessionMonitorService _sessionMonitor = SessionMonitorService();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    // Use Future.microtask to avoid setState during build
    Future.microtask(() => _loadData());

    // Start session monitoring
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _sessionMonitor.startMonitoring(context);
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);

    switch (state) {
      case AppLifecycleState.resumed:
        // App came to foreground, check session validity
        _checkSessionOnResume();
        break;
      case AppLifecycleState.paused:
      case AppLifecycleState.detached:
        // App went to background, stop monitoring to save resources
        _sessionMonitor.stopMonitoring();
        break;
      default:
        break;
    }
  }

  Future<void> _checkSessionOnResume() async {
    // Check if session is still valid when app resumes
    final authService = AuthService();
    if (!await authService.isLoggedIn()) {
      // Session expired while app was in background
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Session expired. Please login again.'),
            backgroundColor: Colors.orange,
            duration: Duration(seconds: 3),
          ),
        );
        Navigator.of(context).pushNamedAndRemoveUntil(
          '/password-login',
          (route) => false,
        );
      }
    } else {
      // Session is still valid, restart monitoring
      _sessionMonitor.startMonitoring(context);
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _searchController.dispose();
    _sessionMonitor.stopMonitoring();
    super.dispose();
  }

  Future<void> _loadData() async {
    final orderProvider = Provider.of<OrderProvider>(context, listen: false);
    await orderProvider.loadOrders();
  }

  void _onSearchChanged(String query) {
    final orderProvider = Provider.of<OrderProvider>(context, listen: false);
    orderProvider.setSearchQuery(query);
  }

  Future<void> _openGoogleMaps(String address) async {
    try {
      // Clean and encode the address properly
      final cleanAddress = address.trim();
      if (cleanAddress.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Address is empty'),
            backgroundColor: Colors.orange,
          ),
        );
        return;
      }

      // Try multiple URL schemes for better compatibility
      final encodedAddress = Uri.encodeComponent(cleanAddress);

      // 1. Try Google Maps app with geo: scheme (most reliable)
      final geoUri = Uri.parse('geo:0,0?q=$encodedAddress');
      print('Trying geo URI: $geoUri');
      if (await canLaunchUrl(geoUri)) {
        print('Geo URI can be launched');
        await launchUrl(geoUri, mode: LaunchMode.externalApplication);
        return;
      }

      // 2. Try Google Maps app with google.navigation: scheme
      final navUri = Uri.parse('google.navigation:q=$encodedAddress');
      print('Trying navigation URI: $navUri');
      if (await canLaunchUrl(navUri)) {
        print('Navigation URI can be launched');
        await launchUrl(navUri, mode: LaunchMode.externalApplication);
        return;
      }

      // 3. Try Google Maps web URL
      final mapsUri =
          Uri.parse('https://maps.google.com/maps?q=$encodedAddress');
      print('Trying maps URI: $mapsUri');
      if (await canLaunchUrl(mapsUri)) {
        print('Maps URI can be launched');
        await launchUrl(mapsUri, mode: LaunchMode.externalApplication);
        return;
      }

      // 4. Try simple Google Maps search
      final searchUri =
          Uri.parse('https://www.google.com/maps/search/$encodedAddress');
      print('Trying search URI: $searchUri');
      if (await canLaunchUrl(searchUri)) {
        print('Search URI can be launched');
        await launchUrl(searchUri, mode: LaunchMode.externalApplication);
        return;
      }

      // 5. Final fallback: Try to open any browser
      final browserUri = Uri.parse('https://www.google.com/maps');
      print('Trying browser fallback: $browserUri');
      if (await canLaunchUrl(browserUri)) {
        print('Browser fallback can be launched');
        await launchUrl(browserUri, mode: LaunchMode.externalApplication);
        return;
      }

      // If all fail, show error
      print('All URI schemes failed');
      throw Exception('Could not open Google Maps');
    } catch (error) {
      print('Error opening Google Maps: $error');
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Could not open Google Maps'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('NPC Technician'),
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: [
          _buildHomePage(),
          _buildOrdersPage(),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        type: BottomNavigationBarType.fixed,
        selectedItemColor: AppTheme.primaryColor,
        unselectedItemColor: AppTheme.textLightColor,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.assignment),
            label: 'Orders',
          ),
        ],
      ),
    );
  }

  Widget _buildHomePage() {
    return Column(
      children: [
        // Welcome Section
        Consumer<TechnicianProvider>(
          builder: (context, technicianProvider, child) {
            final technician = technicianProvider.technician;
            return Container(
              padding: const EdgeInsets.all(16),
              color: AppTheme.primaryColor.withOpacity(0.1),
              child: Row(
                children: [
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: Colors.black,
                        width: 2,
                      ),
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.asset(
                        'assets/images/logo-npc.png',
                        width: 50,
                        height: 50,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Welcome back,',
                          style:
                              Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    color: AppTheme.textLightColor,
                                  ),
                        ),
                        Text(
                          technician?.name ?? 'Technician',
                          style:
                              Theme.of(context).textTheme.titleLarge?.copyWith(
                                    fontWeight: FontWeight.bold,
                                  ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.logout),
                    onPressed: () async {
                      // Stop session monitoring
                      _sessionMonitor.stopMonitoring();

                      // Logout from provider
                      await technicianProvider.logout();

                      // Navigate to login screen
                      Navigator.of(context)
                          .pushReplacementNamed('/password-login');
                    },
                  ),
                ],
              ),
            );
          },
        ),

        // Dashboard Header
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
          child: Align(
            alignment: Alignment.centerLeft,
            child: Text(
              'Dashboard',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: AppTheme.primaryColor,
                  ),
            ),
          ),
        ),

        // Stats Cards
        Consumer<OrderProvider>(
          builder: (context, orderProvider, child) {
            final stats = orderProvider.orderStats;
            return Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: StatsCard(
                          title: 'Total Orders',
                          value: stats['total'].toString(),
                          icon: Icons.assignment,
                          color: AppTheme.primaryColor,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: StatsCard(
                          title: 'Assigned',
                          value: stats['assigned'].toString(),
                          icon: Icons.pending,
                          color: Colors.orange,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: StatsCard(
                          title: 'Active',
                          value: stats['active'].toString(),
                          icon: Icons.play_circle,
                          color: Colors.blue,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: StatsCard(
                          title: 'Completed',
                          value: stats['completed'].toString(),
                          icon: Icons.check_circle,
                          color: Colors.green,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        ),

        // Recent Activity Section
        Expanded(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Recent Activity',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 16),
                Consumer<OrderProvider>(
                  builder: (context, orderProvider, child) {
                    final recentOrders =
                        orderProvider.filteredOrders.take(3).toList();

                    if (recentOrders.isEmpty) {
                      return Expanded(
                        child: Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.inbox_outlined,
                                size: 64,
                                color: AppTheme.textLightColor,
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'No recent orders',
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Your recent orders will appear here',
                                style: Theme.of(context)
                                    .textTheme
                                    .bodyMedium
                                    ?.copyWith(
                                      color: AppTheme.textLightColor,
                                    ),
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    return Expanded(
                      child: ListView.builder(
                        itemCount: recentOrders.length,
                        itemBuilder: (context, index) {
                          final order = recentOrders[index];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 8),
                            child: ListTile(
                              leading: CircleAvatar(
                                backgroundColor:
                                    _getStatusColor(order.technicianStatus),
                                child: Icon(
                                  _getStatusIcon(order.technicianStatus),
                                  color: Colors.white,
                                  size: 20,
                                ),
                              ),
                              title: Text(
                                'Order #${order.bookingId}',
                                style: const TextStyle(
                                    fontWeight: FontWeight.bold),
                              ),
                              subtitle: Text(
                                '${order.serviceName} - ${order.technicianStatus}',
                                style:
                                    TextStyle(color: AppTheme.textLightColor),
                              ),
                              trailing: Text(
                                DateFormat('MMM dd, yyyy')
                                    .format(order.serviceDate),
                                style: TextStyle(
                                  color: AppTheme.textLightColor,
                                  fontSize: 12,
                                ),
                              ),
                              onTap: () {
                                orderProvider.selectOrder(order);
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) =>
                                        OrderDetailsScreen(order: order),
                                  ),
                                );
                              },
                            ),
                          );
                        },
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildOrdersPage() {
    return Column(
      children: [
        // Search Bar and Filter
        Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Expanded(
                child: CustomSearchBar(
                  controller: _searchController,
                  onChanged: _onSearchChanged,
                  hintText: 'Search orders...',
                ),
              ),
              const SizedBox(width: 12),
              Container(
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: IconButton(
                  icon: const Icon(
                    Icons.filter_list,
                    color: Colors.white,
                  ),
                  onPressed: () => _showFilterDialog(context),
                ),
              ),
            ],
          ),
        ),

        // Filter Tabs
        Container(
          color: Colors.white,
          child: DefaultTabController(
            length: 3,
            child: Column(
              children: [
                TabBar(
                  onTap: (index) {
                    String filter = 'assigned';
                    switch (index) {
                      case 0:
                        filter = 'assigned';
                        break;
                      case 1:
                        filter = 'active';
                        break;
                      case 2:
                        filter = 'completed';
                        break;
                    }

                    final orderProvider =
                        Provider.of<OrderProvider>(context, listen: false);
                    orderProvider.setFilter(filter);
                  },
                  labelColor: AppTheme.primaryColor,
                  unselectedLabelColor: AppTheme.textLightColor,
                  indicatorColor: AppTheme.primaryColor,
                  tabs: const [
                    Tab(text: 'Assigned'),
                    Tab(text: 'Active'),
                    Tab(text: 'Completed'),
                  ],
                ),
              ],
            ),
          ),
        ),

        // Orders List
        Expanded(
          child: Consumer<OrderProvider>(
            builder: (context, orderProvider, child) {
              if (orderProvider.isLoading) {
                return const Center(
                  child: CircularProgressIndicator(),
                );
              }

              if (orderProvider.error != null) {
                return Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.error_outline,
                        size: 64,
                        color: AppTheme.errorColor,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Error loading orders',
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        orderProvider.error!,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: AppTheme.textLightColor,
                            ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: () => orderProvider.loadOrders(),
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                );
              }

              if (orderProvider.filteredOrders.isEmpty) {
                return Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.inbox_outlined,
                        size: 64,
                        color: AppTheme.textLightColor,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'No orders found',
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'You don\'t have any orders in this category',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: AppTheme.textLightColor,
                            ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                );
              }

              return RefreshIndicator(
                onRefresh: () => orderProvider.refreshOrders(),
                child: ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: orderProvider.filteredOrders.length,
                  itemBuilder: (context, index) {
                    final order = orderProvider.filteredOrders[index];
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: OrderCard(
                        order: order,
                        onTap: () {
                          orderProvider.selectOrder(order);
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) =>
                                  OrderDetailsScreen(order: order),
                            ),
                          );
                        },
                        onUpdateStatus: (String newStatus) async {
                          try {
                            await orderProvider.updateOrderStatus(
                                order.bookingId, newStatus);
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(
                                      'Order status updated to $newStatus'),
                                  backgroundColor: Colors.green,
                                ),
                              );
                            }
                          } catch (e) {
                            // Don't show error message for failed status updates (like website)
                            print('Status update failed: $e');

                            // If completing service fails, still refresh to show current state (like website)
                            if (newStatus.toLowerCase() == 'completed') {
                              if (mounted) {
                                _loadData(); // Refresh the orders immediately
                              }
                            }
                          }
                        },
                      ),
                    );
                  },
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  void _showFilterDialog(BuildContext context) {
    String selectedStatus = 'assigned';
    DateTime? selectedDate;
    String selectedAmountRange = 'all';
    final TextEditingController dateController = TextEditingController();
    final TextEditingController amountController = TextEditingController();

    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Filter Orders'),
              IconButton(
                icon: const Icon(Icons.close),
                onPressed: () {
                  Navigator.of(context).pop();
                },
              ),
            ],
          ),
          content: StatefulBuilder(
            builder: (BuildContext context, StateSetter setState) {
              return SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Status Filter
                    const Text(
                      'Order Status',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: selectedStatus,
                          isExpanded: true,
                          hint: const Text('Select Status'),
                          items: const [
                            DropdownMenuItem(
                                value: 'assigned', child: Text('Assigned')),
                            DropdownMenuItem(
                                value: 'active', child: Text('Active')),
                            DropdownMenuItem(
                                value: 'completed', child: Text('Completed')),
                          ],
                          onChanged: (value) {
                            setState(() {
                              selectedStatus = value!;
                            });
                          },
                        ),
                      ),
                    ),

                    const SizedBox(height: 20),

                    // Date Filter
                    const Text(
                      'Date Filter',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: dateController,
                      readOnly: true,
                      decoration: InputDecoration(
                        hintText: 'Select Date',
                        suffixIcon: IconButton(
                          icon: const Icon(Icons.calendar_today),
                          onPressed: () async {
                            final DateTime? picked = await showDatePicker(
                              context: context,
                              initialDate: DateTime.now(),
                              firstDate: DateTime(2020),
                              lastDate:
                                  DateTime.now().add(const Duration(days: 365)),
                              builder: (context, child) {
                                return Theme(
                                  data: Theme.of(context).copyWith(
                                    colorScheme:
                                        Theme.of(context).colorScheme.copyWith(
                                              surface: Colors.white,
                                              onSurface: Colors.black,
                                            ),
                                    dialogBackgroundColor: Colors.white,
                                  ),
                                  child: child!,
                                );
                              },
                            );
                            if (picked != null) {
                              setState(() {
                                selectedDate = picked;
                                dateController.text =
                                    DateFormat('dd MMM yyyy').format(picked);
                              });
                            }
                          },
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                    ),

                    const SizedBox(height: 20),

                    // Amount Filter
                    const Text(
                      'Amount Range',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: selectedAmountRange,
                          isExpanded: true,
                          hint: const Text('Select Amount Range'),
                          items: const [
                            DropdownMenuItem(
                                value: 'all', child: Text('All Amounts')),
                            DropdownMenuItem(
                                value: '0-1000', child: Text('₹0 - ₹1,000')),
                            DropdownMenuItem(
                                value: '1000-5000',
                                child: Text('₹1,000 - ₹5,000')),
                            DropdownMenuItem(
                                value: '5000-10000',
                                child: Text('₹5,000 - ₹10,000')),
                            DropdownMenuItem(
                                value: '10000+', child: Text('₹10,000+')),
                          ],
                          onChanged: (value) {
                            setState(() {
                              selectedAmountRange = value!;
                            });
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
          actions: [
            TextButton(
              onPressed: () {
                setState(() {
                  selectedStatus = 'all';
                  selectedDate = null;
                  selectedAmountRange = 'all';
                  dateController.clear();
                });
              },
              child: const Text('Clear Filter'),
            ),
            ElevatedButton(
              onPressed: () {
                final orderProvider =
                    Provider.of<OrderProvider>(context, listen: false);
                orderProvider.setFilter(selectedStatus);
                // TODO: Implement date and amount filtering in OrderProvider
                Navigator.of(context).pop();
              },
              child: const Text('Apply'),
            ),
          ],
        );
      },
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'assigned':
        return Colors.orange;
      case 'reached':
      case 'started':
        return Colors.blue;
      case 'completed':
        return Colors.green;
      default:
        return AppTheme.primaryColor;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'assigned':
        return Icons.pending;
      case 'reached':
        return Icons.location_on;
      case 'started':
        return Icons.play_circle;
      case 'completed':
        return Icons.check_circle;
      default:
        return Icons.assignment;
    }
  }
}
