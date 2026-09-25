import 'package:flutter/material.dart';

enum LayoutItemType { zone, aisle, rack, shelf, bin }

extension LayoutItemTypeExt on LayoutItemType {
  String get label {
    switch (this) {
      case LayoutItemType.zone: return 'منطقه';
      case LayoutItemType.aisle: return 'راهرو';
      case LayoutItemType.rack: return 'قفسه';
      case LayoutItemType.shelf: return 'طبقه';
      case LayoutItemType.bin: return 'محفظه';
    }
  }

  Color get color {
    switch (this) {
      case LayoutItemType.zone: return const Color(0xFFE3F2FD);
      case LayoutItemType.aisle: return const Color(0xFFF5F5F5);
      case LayoutItemType.rack: return const Color(0xFF1976D2);
      case LayoutItemType.shelf: return const Color(0xFF66BB6A);
      case LayoutItemType.bin: return const Color(0xFFFFA726);
    }
  }

  String get value => name;
}

class LayoutItem {
  final String id;
  String code;
  String name;
  LayoutItemType type;
  double x;
  double y;
  double width;
  double depth;
  double height;
  int? capacity;

  LayoutItem({
    required this.id,
    required this.code,
    required this.name,
    required this.type,
    required this.x,
    required this.y,
    this.width = 2,
    this.depth = 1,
    this.height = 3,
    this.capacity,
  });

  factory LayoutItem.fromApi(Map<String, dynamic> json) {
    return LayoutItem(
      id: json['id'].toString(),
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      type: LayoutItemType.values.firstWhere(
        (e) => e.value == json['type'],
        orElse: () => LayoutItemType.rack,
      ),
      x: (json['position']?['x'] ?? 0).toDouble(),
      y: (json['position']?['y'] ?? 0).toDouble(),
      width: (json['dimensions']?['width'] ?? 2).toDouble(),
      depth: (json['dimensions']?['depth'] ?? 1).toDouble(),
      height: (json['dimensions']?['height'] ?? 3).toDouble(),
      capacity: json['capacity'],
    );
  }

  Map<String, dynamic> toApi() => {
        'code': code,
        'name': name,
        'type': type.value,
        'pos_x': x,
        'pos_y': y,
        'width': width,
        'depth': depth,
        'height': height,
        'capacity': capacity,
      };

  LayoutItem copy() => LayoutItem(
        id: id,
        code: code,
        name: name,
        type: type,
        x: x,
        y: y,
        width: width,
        depth: depth,
        height: height,
        capacity: capacity,
      );
}
