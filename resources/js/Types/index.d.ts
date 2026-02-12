/* eslint-disable @typescript-eslint/no-explicit-any */
import type { Config } from 'ziggy-js';

export interface User {
    id: string;
    name: string;
    email: string;
    avatar_url?: string;
    email_verified_at?: string;
    tenant_id: string;
    created_at: string;
    updated_at: string;
    roles: Role[];
    permissions: string[];
}

export interface Role {
    id: string;
    name: string;
    guard_name: string;
}

export interface Plan {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    billing_period: string;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    max_cameras: number;
    max_users: number;
    max_storage_gb: number;
    retention_days: number;
    max_concurrent_streams: number;
    max_resolution: string;
    max_fps: number;
    has_ai_detection: boolean;
    has_ai_recognition: boolean;
    has_analytics: boolean;
    has_mobile_app: boolean;
    has_api_access: boolean;
    has_webrtc: boolean;
    has_ptz_control: boolean;
    has_custom_alerts: boolean;
    has_export: boolean;
    has_multi_tenant: boolean;
    has_sla: boolean;
    has_onpremise: boolean;
    has_priority_support: boolean;
    has_dedicated_support: boolean;
    metadata: Record<string, any>;
    created_at: string;
    updated_at: string;
}

export interface Tenant {
    id: string;
    name: string;
    slug: string;
    domain?: string;
    logo_url?: string;
    is_active: boolean;
    settings: Record<string, any>;
    created_at: string;
    updated_at: string;
}

export interface Device {
    id: string;
    tenant_id: string;
    name: string;
    slug: string;
    type: 'dvr' | 'nvr' | 'encoder' | 'camera' | 'hybrid';
    manufacturer: string;
    model: string | null;
    firmware_version: string | null;
    address_type: 'ipv4' | 'ipv6' | 'ddns' | 'hostname';
    address: string;
    port: number;
    http_port: number;
    rtmp_port: number;
    secondary_address: string | null;
    transport: 'tcp' | 'udp' | 'auto';
    username: string | null;
    total_channels: number;
    active_channels: number;
    onvif_supported: boolean;
    onvif_profile: string | null;
    is_active: boolean;
    is_online: boolean;
    last_seen_at: string | null;
    uptime_seconds: number;
    ddns_provider: string | null;
    ddns_hostname: string | null;
    ipv6_preferred: boolean;
    last_resolved_ip: string | null;
    last_resolved_at: string | null;
    location: string | null;
    serial_number: string | null;
    metadata: Record<string, any>;
    storage_used_gb: number | null;
    storage_updated_at: string | null;
    pack_model: string | null;
    hls_enabled: boolean;
    hls_fmp4_enabled: boolean;
    low_latency_hls_enabled: boolean;
    created_at: string;
    updated_at: string;
    tenant?: Tenant;
    cameras?: Camera[];
    cameras_count?: number;
}

export interface Camera {
    id: string;
    tenant_id: string;
    device_id: string | null;
    name: string;
    slug: string;
    channel_number: number | null;
    channel_type: 'analog' | 'ip' | 'direct';
    manufacturer: string | null;
    model: string | null;
    address_type: 'ipv4' | 'ipv6' | 'ddns' | 'hostname';
    address: string | null;
    port: number;
    secondary_address: string | null;
    transport: 'tcp' | 'udp' | 'auto';
    username: string | null;
    rtsp_path: string | null;
    rtmp_path: string | null;
    hls_path: string | null;
    stream_type: 'main' | 'sub' | 'mobile';
    codec: string | null;
    resolution: string | null;
    fps: number | null;
    ddns_provider: string | null;
    ddns_hostname: string | null;
    ipv6_preferred: boolean;
    last_resolved_ip: string | null;
    last_resolved_at: string | null;
    has_ptz: boolean;
    has_audio: boolean;
    is_active: boolean;
    is_online: boolean;
    is_recording: boolean;
    last_seen_at: string | null;
    location: string | null;
    description: string | null;
    snapshot_url: string | null;
    metadata: Record<string, any>;
    created_at: string;
    updated_at: string;
    tenant?: Tenant;
    device?: Device;
}

export interface Stream {
    id: string;
    camera_id: string;
    tenant_id: string;
    status: 'starting' | 'active' | 'error' | 'stopped' | 'reconnecting';
    mediamtx_path: string;
    hls_url: string | null;
    webrtc_url: string | null;
    rtsp_input_url: string | null;
    pid: number | null;
    started_at: string | null;
    stopped_at: string | null;
    last_health_check: string | null;
    error_message: string | null;
    metadata: Record<string, any>;
    created_at: string;
    updated_at: string;
    camera?: Camera;
}

export interface Recording {
    id: string;
    camera_id: string;
    tenant_id: string;
    stream_id: string | null;
    status: 'recording' | 'completed' | 'failed' | 'deleted';
    storage_path: string;
    file_size_bytes: number;
    duration_seconds: number;
    started_at: string;
    ended_at: string | null;
    codec: string | null;
    resolution: string | null;
    fps: number | null;
    metadata: Record<string, any>;
    created_at: string;
    updated_at: string;
    camera?: Camera;
}

export interface StorageSummary {
    used_gb: number;
    limit_gb: number;
    percentage: number;
    recordings_count: number;
    oldest_recording: string | null;
}

export interface CameraStorage {
    id: string;
    name: string;
    is_recording: boolean;
    storage_gb: number;
    recording_count: number;
}

export interface StreamUrls {
    rtsp_url: string | null;
    rtmp_url: string | null;
    hls_url: string | null;
    hls_shift_url: string | null;
    mp4_export_url: string | null;
}

export interface ManufacturerOption {
    value: string;
    label: string;
    onvif: boolean;
    ptz: boolean;
    audio: boolean;
}

export type DeviceType = Device['type'];
export type AddressType = Device['address_type'];
export type TransportType = Device['transport'];
export type ChannelType = Camera['channel_type'];
export type StreamType = Camera['stream_type'];

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
}

export interface ApiToken {
    id: string;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
}

export type ApiTokenAbility =
    | 'cameras:read' | 'cameras:write' | 'cameras:delete'
    | 'devices:read' | 'devices:write' | 'devices:delete'
    | 'streams:read' | 'streams:write'
    | 'recordings:read' | 'recordings:write' | 'recordings:download' | 'recordings:delete'
    | 'storage:read' | 'storage:write'
    | 'users:read' | 'users:write'
    | 'profile:read' | 'profile:write'
    | 'mobile:dashboard' | 'mobile:sync'
    | 'admin:*';

export interface UserDevice {
    id: string;
    device_id: string;
    device_name: string;
    platform: 'ios' | 'android' | 'web';
    app_version: string | null;
    os_version: string | null;
    is_active: boolean;
    has_push_token: boolean;
    push_provider: 'fcm' | 'apns' | null;
    last_active_at: string | null;
    revoked_at: string | null;
    created_at: string;
}

export interface Flash {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
    newToken?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        tenant: Tenant;
    };
    flash: Flash;
    ziggy: Config & { location: string };
};
