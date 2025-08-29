import 'package:flutter/material.dart';

class Order {
  final String id;
  final String bookingId;
  final String customerName;
  final String customerPhone;
  final String customerEmail;
  final String customerAddress;
  final String serviceAddress;
  final String serviceName;
  final String serviceType;
  final double amount;
  final String paymentStatus;
  final String orderStatus;
  final String technicianStatus;
  final DateTime serviceDate;
  final String serviceTime;
  final String timeSlot;
  final String? notes;
  final String? specialNotes;
  final String? lastNotes;
  final String? technicianNotes;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Map<String, dynamic>? serviceDetails;
  final List<String>? images;

  Order({
    required this.id,
    required this.bookingId,
    required this.customerName,
    required this.customerPhone,
    required this.customerEmail,
    required this.customerAddress,
    required this.serviceAddress,
    required this.serviceName,
    required this.serviceType,
    required this.amount,
    required this.paymentStatus,
    required this.orderStatus,
    required this.technicianStatus,
    required this.serviceDate,
    required this.serviceTime,
    required this.timeSlot,
    this.notes,
    this.specialNotes,
    this.lastNotes,
    this.technicianNotes,
    required this.createdAt,
    required this.updatedAt,
    this.serviceDetails,
    this.images,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id'] ?? '',
      bookingId: json['booking_id'] ?? '',
      customerName: json['customer_name'] ?? '',
      customerPhone: json['customer_phone'] ?? json['mobile_number'] ?? '',
      customerEmail: json['customer_email'] ?? json['email_id'] ?? '',
      customerAddress: json['customer_address'] ?? '',
      serviceAddress: json['service_address'] ?? json['address'] ?? '',
      serviceName: json['service_name'] ?? '',
      serviceType: json['service_type'] ?? '',
      amount: double.tryParse(json['total_amount']?.toString() ?? '0') ?? 0.0,
      paymentStatus: json['payment_status'] ?? 'pending',
      orderStatus: json['order_status'] ?? json['booking_status'] ?? 'pending',
      technicianStatus: json['technician_status'] ?? 'assigned',
      serviceDate:
          DateTime.tryParse(json['service_date'] ?? '') ?? DateTime.now(),
      serviceTime: json['service_time'] ?? '',
      timeSlot: json['time_slot'] ?? '',
      notes: json['notes'],
      specialNotes: json['special_notes'] ?? json['special_instructions'],
      lastNotes: json['last_notes'],
      technicianNotes: json['technician_notes'],
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      updatedAt: DateTime.tryParse(json['updated_at'] ?? '') ?? DateTime.now(),
      serviceDetails: json['service_details'] != null
          ? Map<String, dynamic>.from(json['service_details'])
          : null,
      images: json['images'] != null ? List<String>.from(json['images']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'booking_id': bookingId,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_email': customerEmail,
      'customer_address': customerAddress,
      'service_address': serviceAddress,
      'service_name': serviceName,
      'service_type': serviceType,
      'total_amount': amount,
      'payment_status': paymentStatus,
      'order_status': orderStatus,
      'technician_status': technicianStatus,
      'service_date': serviceDate.toIso8601String(),
      'service_time': serviceTime,
      'time_slot': timeSlot,
      'notes': notes,
      'special_notes': specialNotes,
      'last_notes': lastNotes,
      'technician_notes': technicianNotes,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
      'service_details': serviceDetails,
      'images': images,
    };
  }

  Order copyWith({
    String? id,
    String? bookingId,
    String? customerName,
    String? customerPhone,
    String? customerEmail,
    String? customerAddress,
    String? serviceAddress,
    String? serviceName,
    String? serviceType,
    double? amount,
    String? paymentStatus,
    String? orderStatus,
    String? technicianStatus,
    DateTime? serviceDate,
    String? serviceTime,
    String? timeSlot,
    String? notes,
    String? specialNotes,
    String? lastNotes,
    String? technicianNotes,
    DateTime? createdAt,
    DateTime? updatedAt,
    Map<String, dynamic>? serviceDetails,
    List<String>? images,
  }) {
    return Order(
      id: id ?? this.id,
      bookingId: bookingId ?? this.bookingId,
      customerName: customerName ?? this.customerName,
      customerPhone: customerPhone ?? this.customerPhone,
      customerEmail: customerEmail ?? this.customerEmail,
      customerAddress: customerAddress ?? this.customerAddress,
      serviceAddress: serviceAddress ?? this.serviceAddress,
      serviceName: serviceName ?? this.serviceName,
      serviceType: serviceType ?? this.serviceType,
      amount: amount ?? this.amount,
      paymentStatus: paymentStatus ?? this.paymentStatus,
      orderStatus: orderStatus ?? this.orderStatus,
      technicianStatus: technicianStatus ?? this.technicianStatus,
      serviceDate: serviceDate ?? this.serviceDate,
      serviceTime: serviceTime ?? this.serviceTime,
      timeSlot: timeSlot ?? this.timeSlot,
      notes: notes ?? this.notes,
      specialNotes: specialNotes ?? this.specialNotes,
      lastNotes: lastNotes ?? this.lastNotes,
      technicianNotes: technicianNotes ?? this.technicianNotes,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      serviceDetails: serviceDetails ?? this.serviceDetails,
      images: images ?? this.images,
    );
  }

  bool get isAssigned => technicianStatus.toLowerCase() == 'assigned';
  bool get isReached => technicianStatus.toLowerCase() == 'reached';
  bool get isStarted => technicianStatus.toLowerCase() == 'started';
  bool get isCompleted => technicianStatus.toLowerCase() == 'completed';
  bool get isCancelled => technicianStatus.toLowerCase() == 'cancelled';

  String get statusDisplayText {
    switch (technicianStatus.toLowerCase()) {
      case 'assigned':
        return 'Assigned';
      case 'reached':
        return 'Reached Location';
      case 'started':
        return 'Service Started';
      case 'completed':
        return 'Completed';
      case 'cancelled':
        return 'Cancelled';
      default:
        return 'Unknown';
    }
  }

  Color get statusColor {
    switch (technicianStatus.toLowerCase()) {
      case 'assigned':
        return Colors.orange;
      case 'reached':
        return Colors.blue;
      case 'started':
        return Colors.purple;
      case 'completed':
        return Colors.green;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  String toString() {
    return 'Order(id: $id, bookingId: $bookingId, customerName: $customerName, status: $technicianStatus)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is Order && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
