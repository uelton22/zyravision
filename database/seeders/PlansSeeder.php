<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        Plan::firstOrCreate(['slug' => 'starter'], [
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Ideal para pequenas empresas e residências.',
            'price' => 197.00,
            'billing_period' => 'monthly',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
            // Quotas
            'max_cameras' => 16,
            'max_users' => 3,
            'max_storage_gb' => 50,
            'retention_days' => 7,
            'max_concurrent_streams' => 4,
            'max_resolution' => '1080p',
            'max_fps' => 15,
            // Features
            'has_ai_detection' => false,
            'has_ai_recognition' => false,
            'has_analytics' => false,
            'has_mobile_app' => false,
            'has_api_access' => false,
            'has_webrtc' => false,
            'has_ptz_control' => true,
            'has_custom_alerts' => false,
            'has_export' => false,
            'has_multi_tenant' => false,
            'has_sla' => false,
            'has_onpremise' => false,
            'has_priority_support' => false,
            'has_dedicated_support' => false,
        ]);

        Plan::firstOrCreate(['slug' => 'professional'], [
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Para empresas em crescimento que precisam de mais recursos.',
            'price' => 497.00,
            'billing_period' => 'monthly',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 2,
            // Quotas
            'max_cameras' => 64,
            'max_users' => 10,
            'max_storage_gb' => 500,
            'retention_days' => 30,
            'max_concurrent_streams' => 16,
            'max_resolution' => '1080p',
            'max_fps' => 30,
            // Features
            'has_ai_detection' => true,
            'has_ai_recognition' => false,
            'has_analytics' => true,
            'has_mobile_app' => true,
            'has_api_access' => true,
            'has_webrtc' => false,
            'has_ptz_control' => true,
            'has_custom_alerts' => true,
            'has_export' => true,
            'has_multi_tenant' => false,
            'has_sla' => false,
            'has_onpremise' => false,
            'has_priority_support' => true,
            'has_dedicated_support' => false,
        ]);

        Plan::firstOrCreate(['slug' => 'enterprise'], [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Para grandes operações com necessidades avançadas.',
            'price' => 0, // Sob consulta
            'billing_period' => 'monthly',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 3,
            // Quotas
            'max_cameras' => 10000,
            'max_users' => 1000,
            'max_storage_gb' => 10000,
            'retention_days' => 365,
            'max_concurrent_streams' => 100,
            'max_resolution' => '4k',
            'max_fps' => 60,
            // Features
            'has_ai_detection' => true,
            'has_ai_recognition' => true,
            'has_analytics' => true,
            'has_mobile_app' => true,
            'has_api_access' => true,
            'has_webrtc' => true,
            'has_ptz_control' => true,
            'has_custom_alerts' => true,
            'has_export' => true,
            'has_multi_tenant' => true,
            'has_sla' => true,
            'has_onpremise' => true,
            'has_priority_support' => true,
            'has_dedicated_support' => true,
        ]);
    }
}
