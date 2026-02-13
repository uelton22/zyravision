import '../../entities/dashboard_data.dart';
import '../../repositories/dashboard_repository.dart';

class GetDashboardUseCase {
  final DashboardRepository _repository;

  const GetDashboardUseCase(this._repository);

  Future<DashboardData> call() => _repository.getDashboard();
}
