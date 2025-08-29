import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:intl/intl.dart';
import '../models/order.dart';
import '../utils/theme.dart';
import '../providers/order_provider.dart';
import 'package:provider/provider.dart';

class OrderDetailsScreen extends StatelessWidget {
  final Order order;

  const OrderDetailsScreen({
    super.key,
    required this.order,
  });

  Future<void> _openGoogleMaps(String address) async {
    try {
      // Clean and encode the address properly
      final cleanAddress = address.trim();
      if (cleanAddress.isEmpty) {
        throw Exception('Address is empty');
      }

      // Try multiple URL schemes for better compatibility
      final encodedAddress = Uri.encodeComponent(cleanAddress);

      // 1. Try Google Maps app with geo: scheme
      final geoUri = Uri.parse('geo:0,0?q=$encodedAddress');
      if (await canLaunchUrl(geoUri)) {
        await launchUrl(geoUri, mode: LaunchMode.externalApplication);
        return;
      }

      // 2. Try Google Maps app with google.navigation: scheme
      final navUri = Uri.parse('google.navigation:q=$encodedAddress');
      if (await canLaunchUrl(navUri)) {
        await launchUrl(navUri, mode: LaunchMode.externalApplication);
        return;
      }

      // 3. Try Google Maps web URL
      final mapsUri =
          Uri.parse('https://maps.google.com/maps?q=$encodedAddress');
      if (await canLaunchUrl(mapsUri)) {
        await launchUrl(mapsUri, mode: LaunchMode.externalApplication);
        return;
      }

      // 4. Final fallback: Google Maps search in browser
      final searchUri =
          Uri.parse('https://www.google.com/maps/search/$encodedAddress');
      if (await canLaunchUrl(searchUri)) {
        await launchUrl(searchUri, mode: LaunchMode.externalApplication);
        return;
      }

      // If all fail, show error
      throw Exception('Could not open Google Maps');
    } catch (error) {
      print('Error opening Google Maps: $error');
      rethrow;
    }
  }

  Future<void> _callCustomer(String phoneNumber) async {
    final Uri phoneUri = Uri(scheme: 'tel', path: phoneNumber);
    if (await canLaunchUrl(phoneUri)) {
      await launchUrl(phoneUri);
    } else {
      throw Exception('Could not launch phone dialer');
    }
  }

  String _getNextStatus(String currentStatus) {
    switch (currentStatus.toLowerCase()) {
      case 'assigned':
        return 'reached';
      case 'reached':
        return 'started';
      case 'started':
        return 'completed';
      default:
        return currentStatus;
    }
  }

  String _getNextStatusText(String currentStatus) {
    switch (currentStatus.toLowerCase()) {
      case 'assigned':
        return 'Mark as Reached';
      case 'reached':
        return 'Start Service';
      case 'started':
        return 'Complete Service';
      default:
        return 'Update Status';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Order Details',
          style: TextStyle(color: Colors.white),
        ),
        backgroundColor: AppTheme.primaryColor,
        foregroundColor: Colors.white,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Customer Information
            _buildSection(
              context,
              'Customer Information',
              [
                _buildInfoRow(context, 'Customer Name', order.customerName),
                _buildInfoRow(context, 'Phone', order.customerPhone),
                _buildInfoRow(context, 'Email', order.customerEmail),
              ],
            ),

            const SizedBox(height: 24),

            // Service Information
            _buildSection(
              context,
              'Service Information',
              [
                _buildInfoRow(context, 'Service Name', order.serviceName),
                _buildInfoRow(context, 'Booking ID', order.bookingId),
                _buildInfoRow(context, 'Status', order.statusDisplayText,
                    valueColor: order.statusColor),
              ],
            ),

            const SizedBox(height: 24),

            // Schedule Information
            _buildSection(
              context,
              'Schedule',
              [
                _buildInfoRow(context, 'Service Date',
                    DateFormat('dd MMM yyyy').format(order.serviceDate)),
                _buildInfoRow(
                    context,
                    'Time Slot',
                    order.timeSlot.isNotEmpty
                        ? order.timeSlot
                        : order.serviceTime),
                _buildInfoRow(context, 'Booking Date',
                    DateFormat('dd MMM yyyy').format(order.createdAt)),
              ],
            ),

            const SizedBox(height: 24),

            // Service Address
            _buildAddressSection(context),

            const SizedBox(height: 24),

            // Special Notes
            if (order.specialNotes != null && order.specialNotes!.isNotEmpty)
              _buildNotesSection(context, 'Special Notes', order.specialNotes!),

            const SizedBox(height: 24),

            // Payment Information
            _buildSection(
              context,
              'Payment Information',
              [
                _buildInfoRow(
                    context, 'Amount', '₹${order.amount.toStringAsFixed(2)}'),
                _buildInfoRow(context, 'Payment Status',
                    order.paymentStatus.toUpperCase(),
                    valueColor: order.paymentStatus.toLowerCase() == 'paid'
                        ? AppTheme.successColor
                        : AppTheme.warningColor),
              ],
            ),

            const SizedBox(height: 32),

            // Action Buttons
            if (order.technicianStatus.toLowerCase() != 'completed')
              _buildActionButtons(context),
          ],
        ),
      ),
    );
  }

  Widget _buildSection(
      BuildContext context, String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
                color: AppTheme.primaryColor,
              ),
        ),
        const SizedBox(height: 12),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.grey[50],
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey[200]!),
          ),
          child: Column(
            children: children,
          ),
        ),
      ],
    );
  }

  Widget _buildInfoRow(BuildContext context, String label, String value,
      {Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            '$label:',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Colors.grey[600],
                ),
          ),
          Expanded(
            child: Text(
              value,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w500,
                    color: valueColor ?? Colors.black87,
                  ),
              textAlign: TextAlign.right,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAddressSection(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Service Address',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
                color: AppTheme.primaryColor,
              ),
        ),
        const SizedBox(height: 12),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.grey[50],
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey[200]!),
          ),
          child: Text(
            order.serviceAddress.isNotEmpty
                ? order.serviceAddress
                : order.customerAddress,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Colors.black87,
                ),
          ),
        ),
      ],
    );
  }

  Widget _buildNotesSection(BuildContext context, String title, String notes) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
                color: AppTheme.primaryColor,
              ),
        ),
        const SizedBox(height: 12),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.grey[50],
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey[200]!),
          ),
          child: Text(
            notes,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Colors.black87,
                ),
          ),
        ),
      ],
    );
  }

  Widget _buildActionButtons(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Actions',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
                color: AppTheme.primaryColor,
              ),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            // Directions Button
            ElevatedButton.icon(
              onPressed: () async {
                try {
                  final address = order.serviceAddress.isNotEmpty
                      ? order.serviceAddress
                      : order.customerAddress;
                  await _openGoogleMaps(address);
                } catch (e) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Could not open Google Maps: $e'),
                      backgroundColor: Colors.red,
                    ),
                  );
                }
              },
              icon: const Icon(Icons.map, size: 16),
              label: const Text('Get Directions'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.secondaryColor,
                foregroundColor: Colors.white,
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
            ),

            // Call Button
            ElevatedButton.icon(
              onPressed: () async {
                try {
                  await _callCustomer(order.customerPhone);
                } catch (e) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Could not launch phone dialer: $e'),
                      backgroundColor: Colors.red,
                    ),
                  );
                }
              },
              icon: const Icon(Icons.phone, size: 16),
              label: const Text('Call Customer'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.successColor,
                foregroundColor: Colors.white,
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
            ),

            // Status Update Button
            ElevatedButton.icon(
              onPressed: () async {
                final orderProvider =
                    Provider.of<OrderProvider>(context, listen: false);
                final nextStatus = _getNextStatus(order.technicianStatus);

                try {
                  await orderProvider.updateOrderStatus(
                      order.bookingId, nextStatus);

                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Order status updated to $nextStatus'),
                      backgroundColor: Colors.green,
                    ),
                  );

                  // Navigate back to refresh the list
                  Navigator.pop(context);
                } catch (e) {
                  // Don't show error message for failed status updates (like website)
                  print('Status update failed: $e');

                  // If completing service fails, still refresh to show current state (like website)
                  if (nextStatus.toLowerCase() == 'completed') {
                    Navigator.pop(
                        context); // Navigate back to refresh the list immediately
                  }
                }
              },
              icon: const Icon(Icons.update, size: 16),
              label: Text(_getNextStatusText(order.technicianStatus)),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primaryColor,
                foregroundColor: Colors.white,
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
            ),
          ],
        ),
      ],
    );
  }
}
