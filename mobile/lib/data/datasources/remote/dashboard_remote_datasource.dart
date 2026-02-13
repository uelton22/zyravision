import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/constants.dart';
import '../../../core/network/api_client.dart';
import '../../models/dashboard_model.dart';

final dashboardRemoteDataSourceProvider =
    Provider<DashboardRemoteDataSource>((ref) {
  return DashboardRemoteDataSource(ref.read(apiClientProvider));
});

class DashboardRemoteDataSource {
  final ApiClient _client;

  DashboardRemoteDataSource(this._client);

  Future<DashboardResponseModel> getDashboard() async {
    final response = await _client.dio.get(ApiPaths.dashboard);
    return DashboardResponseModel.fromJson(
      response.data as Map<String, dynamic>,
    );
  }
}
