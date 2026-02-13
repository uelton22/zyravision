import 'package:flutter/material.dart';

class ZyBottomNav extends StatelessWidget {
  final int currentIndex;
  final ValueChanged<int> onTap;
  final int alertBadgeCount;

  const ZyBottomNav({
    super.key,
    required this.currentIndex,
    required this.onTap,
    this.alertBadgeCount = 0,
  });

  @override
  Widget build(BuildContext context) {
    return NavigationBar(
      selectedIndex: currentIndex,
      onDestinationSelected: onTap,
      height: 64,
      labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      destinations: [
        const NavigationDestination(
          icon: Icon(Icons.dashboard_outlined),
          selectedIcon: Icon(Icons.dashboard),
          label: 'Home',
        ),
        const NavigationDestination(
          icon: Icon(Icons.videocam_outlined),
          selectedIcon: Icon(Icons.videocam),
          label: 'Cameras',
        ),
        const NavigationDestination(
          icon: Icon(Icons.play_circle_outline),
          selectedIcon: Icon(Icons.play_circle),
          label: 'Gravacoes',
        ),
        NavigationDestination(
          icon: alertBadgeCount > 0
              ? Badge.count(
                  count: alertBadgeCount,
                  child: const Icon(Icons.notifications_outlined),
                )
              : const Icon(Icons.notifications_outlined),
          selectedIcon: alertBadgeCount > 0
              ? Badge.count(
                  count: alertBadgeCount,
                  child: const Icon(Icons.notifications),
                )
              : const Icon(Icons.notifications),
          label: 'Alertas',
        ),
        const NavigationDestination(
          icon: Icon(Icons.settings_outlined),
          selectedIcon: Icon(Icons.settings),
          label: 'Config',
        ),
      ],
    );
  }
}
