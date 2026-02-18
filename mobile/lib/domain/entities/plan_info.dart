import 'package:freezed_annotation/freezed_annotation.dart';

part 'plan_info.freezed.dart';

@freezed
class PlanInfo with _$PlanInfo {
  const factory PlanInfo({
    required String name,
    @Default(0.0) double price,
    @Default('monthly') String billingCycle,
    @Default(0) int maxCameras,
    @Default(0) int maxUsers,
    @Default(0) int maxStorageGb,
    @Default(0) int retentionDays,
    @Default(0) int maxConcurrentStreams,
    String? maxResolution,
    @Default(0) int maxFps,
    @Default(false) bool hasAiDetection,
    @Default(false) bool hasMobileApp,
    @Default(false) bool hasApiAccess,
  }) = _PlanInfo;

  const PlanInfo._();

  String get priceFormatted {
    if (price <= 0) return 'Gratuito';
    return 'R\$ ${price.toStringAsFixed(2).replaceAll('.', ',')}';
  }

  String get cycleLabel {
    switch (billingCycle) {
      case 'monthly':
        return '/mes';
      case 'yearly':
        return '/ano';
      default:
        return '';
    }
  }
}
