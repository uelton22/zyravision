enum UserRole {
  superAdmin,
  admin,
  operator,
  viewer;

  static UserRole fromString(String value) {
    switch (value) {
      case 'super-admin':
        return UserRole.superAdmin;
      case 'admin':
        return UserRole.admin;
      case 'operator':
        return UserRole.operator;
      case 'viewer':
        return UserRole.viewer;
      default:
        return UserRole.viewer;
    }
  }

  String get label {
    switch (this) {
      case UserRole.superAdmin:
        return 'Super Admin';
      case UserRole.admin:
        return 'Administrador';
      case UserRole.operator:
        return 'Operador';
      case UserRole.viewer:
        return 'Visualizador';
    }
  }
}
