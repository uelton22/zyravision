import 'package:flutter/material.dart';

class ZySpacing {
  ZySpacing._();

  // Scale (base 4px)
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double base = 16;
  static const double lg = 20;
  static const double xl = 24;
  static const double xxl = 32;
  static const double xxxl = 40;
  static const double xxxxl = 48;
  static const double xxxxxl = 64;

  // Border Radius
  static const double radiusSm = 6;
  static const double radiusMd = 8;
  static const double radiusLg = 12;
  static const double radiusXl = 16;
  static const double radiusFull = 9999;

  // Screen
  static const double screenPaddingPhone = 16;
  static const double screenPaddingTablet = 24;
  static const double sectionSpacing = 24;
  static const double listItemSpacing = 12;
  static const double cardPadding = 16;
  static const double appBarHeight = 56;
  static const double bottomNavHeight = 64;

  // EdgeInsets helpers
  static const screenPhone = EdgeInsets.symmetric(horizontal: 16);
  static const screenTablet = EdgeInsets.symmetric(horizontal: 24);
  static const cardInsets = EdgeInsets.all(16);
  static const listInsets = EdgeInsets.symmetric(horizontal: 16, vertical: 8);
  static const inputContentPadding = EdgeInsets.symmetric(horizontal: 12, vertical: 14);

  // BorderRadius helpers
  static final borderRadiusSm = BorderRadius.circular(radiusSm);
  static final borderRadiusMd = BorderRadius.circular(radiusMd);
  static final borderRadiusLg = BorderRadius.circular(radiusLg);
  static final borderRadiusXl = BorderRadius.circular(radiusXl);
  static final borderRadiusFull = BorderRadius.circular(radiusFull);
}
