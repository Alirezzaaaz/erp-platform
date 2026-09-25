class User {
  final int id;
  final String name;
  final String email;
  final String? mobile;
  final String? avatarUrl;
  final String status;
  final Role? role;
  final DateTime? lastLoginAt;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.mobile,
    this.avatarUrl,
    required this.status,
    this.role,
    this.lastLoginAt,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json['id'] as int,
        name: json['name'] as String,
        email: json['email'] as String,
        mobile: json['mobile'] as String?,
        avatarUrl: json['avatar_url'] as String?,
        status: json['status'] as String,
        role: json['role'] != null ? Role.fromJson(json['role']) : null,
        lastLoginAt: json['last_login_at'] != null
            ? DateTime.parse(json['last_login_at'])
            : null,
      );
}

class Role {
  final String name;
  final String displayName;
  final List<String> permissions;

  Role({
    required this.name,
    required this.displayName,
    required this.permissions,
  });

  factory Role.fromJson(Map<String, dynamic> json) => Role(
        name: json['name'] as String,
        displayName: json['display_name'] as String,
        permissions: (json['permissions'] as List<dynamic>?)
                ?.map((e) => e.toString())
                .toList() ??
            [],
      );

  bool hasPermission(String permission) {
    if (permissions.contains('*')) return true;
    return permissions.contains(permission);
  }
}
