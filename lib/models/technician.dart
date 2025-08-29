class Technician {
  final int technicianId;
  final String employeeName;
  final String phoneNumber;
  final String email;

  Technician({
    required this.technicianId,
    required this.employeeName,
    required this.phoneNumber,
    required this.email,
  });

  factory Technician.fromJson(Map<String, dynamic> json) {
    return Technician(
      technicianId: json['technician_id'] ?? json['id'] ?? 0,
      employeeName: json['employee_name'] ?? json['name'] ?? '',
      phoneNumber: json['phone_number'] ?? json['phone'] ?? '',
      email: json['email'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'technician_id': technicianId,
      'employee_name': employeeName,
      'phone_number': phoneNumber,
      'email': email,
    };
  }

  // For backward compatibility
  String get name => employeeName;
  String get phone => phoneNumber;
  String get address => ''; // Not available in website data
}
