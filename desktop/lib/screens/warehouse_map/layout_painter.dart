import 'package:flutter/material.dart';
import '../../models/layout_item.dart';

class LayoutPainter extends CustomPainter {
  final List<LayoutItem> items;
  final double pixelsPerMeter;
  final double gridSize;
  final String? selectedId;
  final bool showGrid;

  LayoutPainter({
    required this.items,
    required this.pixelsPerMeter,
    required this.gridSize,
    this.selectedId,
    this.showGrid = true,
  });

  @override
  void paint(Canvas canvas, Size size) {
    // پس‌زمینه
    canvas.drawRect(
      Rect.fromLTWH(0, 0, size.width, size.height),
      Paint()..color = Colors.white,
    );

    // Grid
    if (showGrid) {
      _drawGrid(canvas, size);
    }

    // آیتم‌ها
    for (final item in items) {
      _drawItem(canvas, item);
    }
  }

  void _drawGrid(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.grey.shade300
      ..strokeWidth = 0.5;

    final step = gridSize * pixelsPerMeter;

    // خطوط عمودی
    for (double x = 0; x < size.width; x += step) {
      canvas.drawLine(Offset(x, 0), Offset(x, size.height), paint);
    }

    // خطوط افقی
    for (double y = 0; y < size.height; y += step) {
      canvas.drawLine(Offset(0, y), Offset(size.width, y), paint);
    }

    // خطوط اصلی هر ۵ متر
    final majorPaint = Paint()
      ..color = Colors.grey.shade400
      ..strokeWidth = 1;

    final majorStep = 5 * pixelsPerMeter;
    for (double x = 0; x < size.width; x += majorStep) {
      canvas.drawLine(Offset(x, 0), Offset(x, size.height), majorPaint);
    }
    for (double y = 0; y < size.height; y += majorStep) {
      canvas.drawLine(Offset(0, y), Offset(size.width, y), majorPaint);
    }
  }

  void _drawItem(Canvas canvas, LayoutItem item) {
    final rect = Rect.fromLTWH(
      item.x * pixelsPerMeter,
      item.y * pixelsPerMeter,
      item.width * pixelsPerMeter,
      item.depth * pixelsPerMeter,
    );

    final isSelected = item.id == selectedId;

    // fill
    final fillPaint = Paint()
      ..color = item.type.color.withValues(alpha: 0.7)
      ..style = PaintingStyle.fill;

    // border
    final borderPaint = Paint()
      ..color = isSelected ? Colors.red : Colors.black87
      ..strokeWidth = isSelected ? 3 : 1.5
      ..style = PaintingStyle.stroke;

    // گوشه‌های گرد
    final rrect = RRect.fromRectAndRadius(rect, const Radius.circular(4));

    canvas.drawRRect(rrect, fillPaint);
    canvas.drawRRect(rrect, borderPaint);

    // برچسب کد
    if (rect.width > 40 && rect.height > 20) {
      final textPainter = TextPainter(
        text: TextSpan(
          text: item.code,
          style: TextStyle(
            color: _textColorFor(item.type),
            fontSize: 11,
            fontWeight: FontWeight.w600,
          ),
        ),
        textDirection: TextDirection.ltr,
      );
      textPainter.layout(maxWidth: rect.width - 8);
      textPainter.paint(
        canvas,
        Offset(
          rect.left + (rect.width - textPainter.width) / 2,
          rect.top + (rect.height - textPainter.height) / 2,
        ),
      );
    }

    // اگر انتخاب شده، دستگیره‌ها را نشان بده
    if (isSelected) {
      final handlePaint = Paint()
        ..color = Colors.red
        ..style = PaintingStyle.fill;

      const handleRadius = 5.0;
      final corners = [
        rect.topLeft,
        rect.topRight,
        rect.bottomLeft,
        rect.bottomRight,
      ];

      for (final c in corners) {
        canvas.drawCircle(c, handleRadius, handlePaint);
        canvas.drawCircle(c, handleRadius, Paint()
          ..color = Colors.white
          ..style = PaintingStyle.stroke
          ..strokeWidth = 2);
      }
    }
  }

  Color _textColorFor(LayoutItemType type) {
    switch (type) {
      case LayoutItemType.zone:
      case LayoutItemType.aisle:
        return Colors.black87;
      default:
        return Colors.white;
    }
  }

  @override
  bool shouldRepaint(covariant LayoutPainter oldDelegate) {
    return oldDelegate.items != items ||
        oldDelegate.selectedId != selectedId ||
        oldDelegate.pixelsPerMeter != pixelsPerMeter;
  }
}
