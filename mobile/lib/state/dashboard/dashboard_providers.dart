import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/repositories/dashboard_repository_impl.dart';
import '../../domain/entities/dashboard_data.dart';
import '../../domain/usecases/dashboard/get_dashboard_usecase.dart';
import 'dashboard_notifier.dart';

final getDashboardUseCaseProvider = Provider<GetDashboardUseCase>((ref) {
  return GetDashboardUseCase(ref.read(dashboardRepositoryProvider));
});

final dashboardProvider =
    AutoDisposeAsyncNotifierProvider<DashboardNotifier, DashboardData>(
  DashboardNotifier.new,
);
