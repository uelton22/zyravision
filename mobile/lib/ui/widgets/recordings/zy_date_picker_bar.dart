import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/timeline_data.dart';

/// Horizontal date picker bar inspired by Camerite.
/// Shows available recording dates with quick navigation.
class ZyDatePickerBar extends StatelessWidget {
  final DateTime selectedDate;
  final List<AvailableDate> availableDates;
  final ValueChanged<DateTime> onDateSelected;

  const ZyDatePickerBar({
    super.key,
    required this.selectedDate,
    required this.availableDates,
    required this.onDateSelected,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      height: 56,
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        border: Border(
          bottom: BorderSide(
            color: theme.colorScheme.outline.withValues(alpha: 0.1),
          ),
        ),
      ),
      child: Row(
        children: [
          // Calendar button
          IconButton(
            onPressed: () => _showDatePicker(context),
            icon: const Icon(Icons.calendar_month),
            color: ZyColors.primary,
          ),
          // Date chips
          Expanded(
            child: _buildDateList(context, theme),
          ),
          // Today button
          TextButton(
            onPressed: () => onDateSelected(DateTime.now()),
            child: const Text('Hoje'),
          ),
        ],
      ),
    );
  }

  Widget _buildDateList(BuildContext context, ThemeData theme) {
    // Show the last 7 days plus available dates
    final now = DateTime.now();
    final days = <DateTime>[];

    // Add last 7 days
    for (var i = 0; i < 7; i++) {
      final d = now.subtract(Duration(days: i));
      days.add(DateTime(d.year, d.month, d.day));
    }

    return ListView.builder(
      scrollDirection: Axis.horizontal,
      itemCount: days.length,
      padding: const EdgeInsets.symmetric(horizontal: ZySpacing.xs),
      itemBuilder: (context, index) {
        final day = days[index];
        final isSelected = _isSameDay(day, selectedDate);
        final hasRecordings = _hasRecordingsForDate(day);

        return _DateChip(
          date: day,
          isSelected: isSelected,
          hasRecordings: hasRecordings,
          onTap: () => onDateSelected(day),
          isToday: index == 0,
        );
      },
    );
  }

  bool _isSameDay(DateTime a, DateTime b) {
    return a.year == b.year && a.month == b.month && a.day == b.day;
  }

  bool _hasRecordingsForDate(DateTime date) {
    return availableDates.any((d) =>
        d.date.year == date.year &&
        d.date.month == date.month &&
        d.date.day == date.day);
  }

  Future<void> _showDatePicker(BuildContext context) async {
    final result = await showDatePicker(
      context: context,
      initialDate: selectedDate,
      firstDate: DateTime(2024),
      lastDate: DateTime.now(),
      locale: const Locale('pt', 'BR'),
    );
    if (result != null) {
      onDateSelected(result);
    }
  }
}

class _DateChip extends StatelessWidget {
  final DateTime date;
  final bool isSelected;
  final bool hasRecordings;
  final VoidCallback onTap;
  final bool isToday;

  const _DateChip({
    required this.date,
    required this.isSelected,
    required this.hasRecordings,
    required this.onTap,
    this.isToday = false,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: ZySpacing.xs,
        vertical: ZySpacing.sm,
      ),
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(
            horizontal: ZySpacing.md,
            vertical: ZySpacing.xs,
          ),
          decoration: BoxDecoration(
            color: isSelected
                ? ZyColors.primary
                : Colors.transparent,
            borderRadius: ZySpacing.borderRadiusFull,
            border: Border.all(
              color: isSelected
                  ? ZyColors.primary
                  : hasRecordings
                      ? ZyColors.primary.withValues(alpha: 0.3)
                      : theme.colorScheme.outline.withValues(alpha: 0.2),
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                isToday ? 'Hoje' : _weekDayShort(date.weekday),
                style: theme.textTheme.labelSmall?.copyWith(
                  color: isSelected
                      ? Colors.white
                      : theme.colorScheme.outline,
                  fontSize: 10,
                ),
              ),
              Text(
                '${date.day}',
                style: theme.textTheme.bodySmall?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: isSelected
                      ? Colors.white
                      : theme.colorScheme.onSurface,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _weekDayShort(int weekday) {
    const days = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom'];
    return days[weekday - 1];
  }
}
