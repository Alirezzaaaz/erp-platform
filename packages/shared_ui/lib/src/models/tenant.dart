class Tenant {
  final int id;
  final String name;
  final String subdomain;
  final String? businessType;
  final String? sizeCategory;

  Tenant({
    required this.id,
    required this.name,
    required this.subdomain,
    this.businessType,
    this.sizeCategory,
  });

  factory Tenant.fromJson(Map<String, dynamic> json) => Tenant(
        id: json['id'] as int,
        name: json['name'] as String,
        subdomain: json['subdomain'] as String,
        businessType: json['business_type'] as String?,
        sizeCategory: json['size_category'] as String?,
      );
}
