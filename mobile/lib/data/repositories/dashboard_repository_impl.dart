import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/dashboard_data.dart';
import '../../domain/repositories/dashboard_repository.dart';
import '../datasources/remote/dashboard_remote_datasource.dart';
import '../mappers/dashboard_mapper.dart';

final dashboardRepositoryProvider = Provider<DashboardRepository>((ref) {
  return DashboardRepositoryImpl(ref.read(dashboardRemoteDataSourceProvider));
});

class DashboardRepositoryImpl implements DashboardRepository {
  final DashboardRemoteDataSource _remote;

  DashboardRepositoryImpl(this._remote);

  @override
  Future<DashboardData> getDashboard() async {
    final response = await _remote.getDashboard();
    return DashboardMapper.toEntity(response.data);
  }
}
