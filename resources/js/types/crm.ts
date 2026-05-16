export type Option = {
    value: string | number | null;
    label: string;
    description?: string;
    color?: string;
    is_won?: boolean;
    is_lost?: boolean;
    company_id?: number;
    email?: string | null;
    phone?: string | null;
    whatsapp?: string | null;
    subject?: string | null;
    body?: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    meta?: Record<string, unknown>;
};

export type EnumValue = {
    value: string;
    label: string;
};

export type CrmOptions = {
    companyStatuses: Option[];
    leadTemperatures: Option[];
    priorities: Option[];
    companySizes: Option[];
    companyTypes: Option[];
    contactTypes: Option[];
    opportunityStatuses: Option[];
    activityTypes: Option[];
    activityStatuses: Option[];
    communicationChannels: Option[];
    communicationDirections: Option[];
    communicationOrigins: Option[];
    communicationStatuses: Option[];
    users?: Option[];
    companies?: Option[];
    contacts?: Option[];
    opportunities?: Option[];
    stages?: Option[];
    templates?: Option[];
    channels?: Option[];
    segments?: string[];
    leadSources?: string[];
    sources?: string[];
};

export type CompanyListItem = {
    id: number;
    legal_name: string;
    trade_name: string | null;
    display_name: string;
    formatted_cnpj: string;
    segment: string | null;
    city: string | null;
    state: string | null;
    status: EnumValue;
    lead_temperature: EnumValue;
    priority: EnumValue;
    responsible_user: { id: number; name: string } | null;
    contacts_count: number;
    last_interaction_at: string | null;
};

export type CompanyFormData = {
    id?: number;
    legal_name: string;
    trade_name: string | null;
    cnpj: string;
    segment: string | null;
    site: string | null;
    phone: string | null;
    email: string | null;
    whatsapp: string | null;
    city: string | null;
    state: string | null;
    address: string | null;
    status: string;
    lead_source: string | null;
    responsible_user_id: number | string | null;
    average_collection_ticket: string | number | null;
    overdue_customers_count: number | string | null;
    total_default_amount: string | number | null;
    approx_customers_count: number | string | null;
    current_system: string | null;
    has_internal_collection_team: boolean | null;
    has_erp_integration: boolean | null;
    portfolio_notes: string | null;
    company_type: string | null;
    company_size: string | null;
    commercial_potential: string | null;
    lead_temperature: string;
    priority: string;
    pain_profile: string | null;
    closing_probability: number | string;
};

export type CompanyDetail = CompanyFormData & {
    id: number;
    display_name: string;
    formatted_cnpj: string;
    formatted_phone: string | null;
    formatted_whatsapp: string | null;
    status_label: string;
    lead_temperature_label: string;
    priority_label: string;
    responsible_user: { id: number; name: string; email: string } | null;
    contacts: CompanyContactItem[];
    opportunities: CompanyOpportunityItem[];
    activities: CompanyActivityItem[];
    communication_messages: CommunicationListItem[];
    timeline_events: TimelineEvent[];
    last_interaction_at: string | null;
    created_at: string | null;
};

export type CompanyOpportunityItem = {
    id: number;
    title: string;
    formatted_estimated_value: string;
    probability: number;
    expected_close_date: string | null;
    status_label: string;
    stage: {
        id: number;
        name: string;
        color: string;
    };
    responsible_user: { id: number; name: string } | null;
};

export type CompanyContactItem = {
    id: number;
    name: string;
    position: string | null;
    department: string | null;
    email: string | null;
    formatted_phone: string | null;
    formatted_whatsapp: string | null;
    type_label: string;
    is_primary: boolean;
    receives_automations: boolean;
};

export type CompanyActivityItem = {
    id: number;
    title: string;
    type_label: string;
    status_label: string;
    status: string;
    priority_label: string;
    due_at: string | null;
    is_overdue: boolean;
    assigned_to: { id: number; name: string };
};

export type ContactListItem = {
    id: number;
    name: string;
    position: string | null;
    department: string | null;
    email: string | null;
    formatted_phone: string | null;
    formatted_whatsapp: string | null;
    type: EnumValue;
    is_primary: boolean;
    receives_automations: boolean;
    company: {
        id: number;
        display_name: string;
        formatted_cnpj: string;
    };
};

export type ContactFormData = {
    id?: number;
    company_id: number | string | null;
    name: string;
    position: string | null;
    department: string | null;
    email: string | null;
    phone: string | null;
    whatsapp: string | null;
    linkedin_url: string | null;
    type: string;
    is_primary: boolean;
    receives_automations: boolean;
    notes: string | null;
    company?: ContactListItem['company'] | null;
};

export type TimelineEvent = {
    id: number;
    type: string;
    title: string;
    description: string | null;
    user_name: string | null;
    contact_name?: string | null;
    occurred_at: string | null;
};

export type ActivityListItem = {
    id: number;
    title: string;
    description: string | null;
    type: EnumValue;
    status: EnumValue;
    priority: EnumValue;
    due_at: string | null;
    is_overdue: boolean;
    company: { id: number; display_name: string };
    contact: { id: number; name: string } | null;
    opportunity: { id: number; title: string } | null;
    assigned_to: { id: number; name: string };
};

export type ActivityFormData = {
    id?: number;
    company_id: number | string | null;
    contact_id: number | string | null;
    opportunity_id: number | string | null;
    assigned_to_user_id: number | string | null;
    type: string;
    status?: string;
    priority: string;
    title: string;
    description: string | null;
    due_at: string;
};

export type CommunicationListItem = {
    id: number;
    channel: EnumValue;
    direction: EnumValue;
    status: EnumValue;
    origin?: EnumValue;
    to_address: string;
    subject: string | null;
    body: string | null;
    notes?: string | null;
    error_message: string | null;
    duration_seconds?: number | null;
    attachments_count?: number;
    created_at: string | null;
    sent_at?: string | null;
    completed_at?: string | null;
    company: { id: number; display_name: string };
    contact: { id: number; name: string };
    opportunity: { id: number; title: string } | null;
    user: { id: number; name: string } | null;
    communication_channel?: { id: number; name: string } | null;
};

export type OpportunityListItem = {
    id: number;
    title: string;
    estimated_value: string | number;
    formatted_estimated_value: string;
    probability: number;
    expected_close_date: string | null;
    source: string | null;
    status: EnumValue;
    company: { id: number; display_name: string };
    stage: {
        id: number;
        name: string;
        color: string;
        is_won: boolean;
        is_lost: boolean;
    };
    responsible_user: { id: number; name: string } | null;
};

export type OpportunityFormData = {
    id?: number;
    company_id: number | string | null;
    contact_id: number | string | null;
    responsible_user_id: number | string | null;
    pipeline_stage_id: number | string | null;
    title: string;
    estimated_value: string | number;
    probability: string | number;
    expected_close_date: string | null;
    source: string | null;
    products_interests: string | null;
    notes: string | null;
    lost_reason: string | null;
    closed_value: string | number | null;
    closed_at: string | null;
};

export type PipelineStage = {
    id: number;
    name: string;
    slug: string;
    position: number;
    color: string;
    is_won: boolean;
    is_lost: boolean;
    total_count: number;
    total_value: number;
    formatted_total_value: string;
    opportunities: PipelineCard[];
};

export type PipelineCard = {
    id: number;
    title: string;
    formatted_estimated_value: string;
    probability: number;
    expected_close_date: string | null;
    source: string | null;
    company: {
        id: number;
        display_name: string;
        lead_temperature: EnumValue;
    };
    responsible_user: { id: number; name: string } | null;
};

export type DashboardCardVariant =
    | 'primary'
    | 'up'
    | 'down'
    | 'info'
    | 'success'
    | 'neutral';

export type DashboardMetricCard = {
    label: string;
    value: string;
    helper: string;
    icon: string;
    variant: DashboardCardVariant;
};

export type DashboardProfile = {
    role: string;
    label: string;
    title: string;
    description: string;
};

export type DashboardPipelineStage = {
    id: number;
    name: string;
    color: string;
    is_won: boolean;
    is_lost: boolean;
    total_count: number;
    total_value: number;
    formatted_total_value: string;
    stalled_count: number;
};

export type DashboardProductivityUser = {
    id: number;
    name: string;
    role_label: string;
    calls: number;
    emails: number;
    whatsapp: number;
    meetings: number;
    tasks: number;
    follow_ups: number;
    completed_activities: number;
};

export type DashboardTopCompany = {
    id: number;
    display_name: string;
    formatted_total_default_amount: string;
    formatted_average_collection_ticket: string;
    overdue_customers_count: number;
};

export type DashboardTodayActivity = {
    id: number;
    title: string;
    type_label: string;
    priority_label: string;
    due_at: string | null;
    company: { id: number; display_name: string };
    assigned_to: { id: number; name: string };
};

export type DashboardStalledOpportunity = {
    id: number;
    title: string;
    formatted_estimated_value: string;
    last_stage_changed_at: string | null;
    company: { id: number; display_name: string };
    stage: { id: number; name: string; color: string };
};

export type DashboardPortfolio = {
    total_default_amount: string;
    average_collection_ticket: string;
    overdue_customers_count: number;
    top_companies: DashboardTopCompany[];
};

export type AutomationAction = {
    type: string;
    label: string;
    title?: string;
    description?: string;
    body?: string;
    activity_type?: string;
    priority?: string;
    due_in_days?: number;
    assigned_to?: string;
    recipient?: string;
};

export type AutomationListItem = {
    id: number;
    name: string;
    description: string | null;
    trigger: EnumValue;
    conditions: Record<string, unknown>;
    actions: AutomationAction[];
    is_active: boolean;
    executions_count: number;
    latest_execution: {
        id: number;
        status: EnumValue;
        executed_at: string | null;
    } | null;
};

export type AutomationExecutionItem = {
    id: number;
    automation_name: string;
    trigger: EnumValue;
    status: EnumValue;
    error_message: string | null;
    executed_at: string | null;
    company: { id: number; display_name: string } | null;
    user: { id: number; name: string } | null;
};

export type ReportCatalogItem = {
    key: string;
    label: string;
    description: string;
    icon: string;
};

export type ReportTable = ReportCatalogItem & {
    columns: Record<string, string>;
    rows: Record<string, string | number | null>[];
    rows_count: number;
};

export type ReportExportItem = {
    id: number;
    report: string;
    report_label: string;
    format: string;
    status: EnumValue;
    file_name: string | null;
    rows_count: number;
    error_message: string | null;
    created_at: string | null;
    completed_at: string | null;
    download_url: string | null;
};

export type SettingsUser = {
    id: number;
    name: string;
    email: string;
    role: EnumValue;
};

export type SettingsPipelineStage = {
    id: number;
    name: string;
    slug: string;
    position: number;
    color: string;
    is_won: boolean;
    is_lost: boolean;
};

export type SettingsTemplate = {
    id: number;
    channel: EnumValue;
    name: string;
    subject: string | null;
    body: string;
    is_active: boolean;
};

export type SettingsOptionValue = {
    id: number;
    group: string;
    key: string;
    label: string;
    color: string | null;
    position: number;
    is_active: boolean;
};

export type SettingsOptionGroup = {
    key: string;
    label: string;
    items: SettingsOptionValue[];
};

export type CommunicationTemplateItem = {
    id: number;
    channel: EnumValue;
    name: string;
    subject: string | null;
    body: string;
    is_active: boolean;
    messages_count: number;
    created_at: string | null;
    updated_at: string | null;
};

export type CommunicationTemplateFormData = {
    id?: number;
    channel: string;
    name: string;
    subject: string | null;
    body: string;
    is_active: boolean;
};

export type CommunicationChannelItem = {
    id: number;
    name: string;
    type: EnumValue;
    provider: EnumValue;
    is_active: boolean;
    is_shared: boolean;
    is_default: boolean;
    messages_count: number;
    users: { id: number; name: string; email: string; role: EnumValue }[];
    updated_at: string | null;
};

export type CommunicationChannelFormData = {
    id?: number;
    name: string;
    type: string;
    provider: string;
    config: Record<string, string | number | null>;
    is_active: boolean;
    is_shared: boolean;
    is_default: boolean;
    user_ids: number[];
};
