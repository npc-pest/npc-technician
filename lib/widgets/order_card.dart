import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/order.dart';
import '../utils/theme.dart';
import '../utils/constants.dart';

class OrderCard extends StatelessWidget {
  final Order order;
  final VoidCallback? onTap;
  final Function(String)? onUpdateStatus;

  const OrderCard({
    super.key,
    required this.order,
    this.onTap,
    this.onUpdateStatus,
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

  IconData _getStatusIcon(String currentStatus) {
    switch (currentStatus.toLowerCase()) {
      case 'assigned':
        return Icons.location_on;
      case 'reached':
        return Icons.play_circle;
      case 'started':
        return Icons.check_circle;
      default:
        return Icons.update;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppConstants.borderRadius),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppConstants.borderRadius),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header with Booking ID and Status
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      'Booking #${order.bookingId}',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                  ),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: order.statusColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: order.statusColor,
                        width: 1,
                      ),
                    ),
                    child: Text(
                      order.statusDisplayText,
                      style: TextStyle(
                        color: order.statusColor,
                        fontSize: AppConstants.captionFontSize,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              // Customer Info
              Row(
                children: [
                  Icon(
                    Icons.person,
                    size: 16,
                    color: AppTheme.textLightColor,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      order.customerName,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            fontWeight: FontWeight.w500,
                          ),
                    ),
                  ),
                ],
              ),

              // Service Info
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(
                    Icons.build,
                    size: 16,
                    color: AppTheme.textLightColor,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      order.serviceName,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            fontWeight: FontWeight.w500,
                          ),
                    ),
                  ),
                ],
              ),

              // Footer with Date and Time
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(
                    Icons.calendar_today,
                    size: 14,
                    color: Colors.black,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    DateFormat('MMM dd, yyyy').format(order.serviceDate),
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Colors.black,
                        ),
                  ),
                  const SizedBox(width: 24),
                  Icon(
                    Icons.access_time,
                    size: 14,
                    color: Colors.black,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    order.timeSlot.isNotEmpty
                        ? order.timeSlot
                        : order.serviceTime,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Colors.black,
                        ),
                  ),
                ],
              ),

              const SizedBox(height: 16),

              // Action Buttons - Left side (Primary actions)
              Row(
                children: [
                  // View Details Button
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: onTap,
                      icon: const Icon(Icons.visibility, size: 16),
                      label: const Text('View Details'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.secondaryColor,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  // Call Button
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () async {
                        try {
                          await _callCustomer(order.customerPhone);
                        } catch (e) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content:
                                  Text('Could not launch phone dialer: $e'),
                              backgroundColor: Colors.red,
                            ),
                          );
                        }
                      },
                      icon: const Icon(Icons.phone, size: 16),
                      label: const Text('Call'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.successColor,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),

              // Status Update Button (only for non-completed orders)
              if (order.technicianStatus.toLowerCase() != 'completed')
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      final nextStatus = _getNextStatus(order.technicianStatus);
                      onUpdateStatus?.call(nextStatus);
                    },
                    icon:
                        Icon(_getStatusIcon(order.technicianStatus), size: 16),
                    label: Text(_getNextStatusText(order.technicianStatus)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor:
                          order.technicianStatus.toLowerCase() == 'started'
                              ? AppTheme.primaryColor
                              : Colors.grey[200],
                      foregroundColor:
                          order.technicianStatus.toLowerCase() == 'started'
                              ? Colors.white
                              : AppTheme.primaryColor,
                      padding: const EdgeInsets.symmetric(vertical: 8),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
