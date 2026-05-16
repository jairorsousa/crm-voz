<?php

namespace App\Enums;

enum AutomationTrigger: string
{
    case OpportunityStageChanged = 'opportunity_stage_changed';
    case MeetingScheduled = 'meeting_scheduled';
    case ProposalNoResponse = 'proposal_no_response';
    case LeadNoInteraction = 'lead_no_interaction';
    case TaskOverdue = 'task_overdue';

    public function label(): string
    {
        return match ($this) {
            self::OpportunityStageChanged => 'Oportunidade mudou de etapa',
            self::MeetingScheduled => 'Reunião agendada',
            self::ProposalNoResponse => 'Proposta sem resposta',
            self::LeadNoInteraction => 'Lead sem interação',
            self::TaskOverdue => 'Tarefa vencida',
        };
    }
}
