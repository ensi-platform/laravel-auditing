<?php

namespace Ensi\LaravelAuditing\Contracts;

use Ensi\LaravelAuditing\Exceptions\AuditableTransitionException;
use Ensi\LaravelAuditing\Exceptions\AuditingException;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\Builder;

interface Auditable
{
    /**
     * Auditable Model audits.
     *
     * @return MorphMany|Builder
     */
    public function audits(): MorphMany|Builder;

    /**
     * Set the Audit event.
     *
     * @param string $event
     *
     * @return Auditable
     */
    public function setAuditEvent(string $event): Auditable;

    /**
     * Get the Audit event that is set.
     *
     * @return string|null
     */
    public function getAuditEvent();

    /**
     * Get the events that trigger an Audit.
     *
     * @return array
     */
    public function getAuditEvents(): array;

    /**
     * Is the model ready for auditing?
     *
     * @return bool
     */
    public function readyForAuditing(): bool;

    /**
     * Return data for an Audit.
     *
     * @throws AuditingException
     *
     * @return array
     */
    public function toAudit(): array;

    /**
     * Get the (Auditable) attributes included in audit.
     *
     * @return array
     */
    public function getAuditInclude(): array;

    /**
     * Get the (Auditable) attributes excluded from audit.
     *
     * @return array
     */
    public function getAuditExclude(): array;

    /**
     * Get the strict audit status.
     *
     * @return bool
     */
    public function getAuditStrict(): bool;

    /**
     * Get the audit (Auditable) timestamps status.
     *
     * @return bool
     */
    public function getAuditTimestamps(): bool;

    /**
     * Get the Audit Driver.
     *
     * @return string|null
     */
    public function getAuditDriver();

    /**
     * Get the Audit threshold.
     *
     * @return int
     */
    public function getAuditThreshold(): int;

    /**
     * Get the Attribute modifiers.
     *
     * @return array
     */
    public function getAttributeModifiers(): array;

    /**
     * Transform the data before performing an audit.
     *
     * @param array $data
     *
     * @return array
     */
    public function transformAudit(array $data): array;

    /**
     * Generate an array with the model tags.
     *
     * @return array
     */
    public function generateTags(): array;

    /**
     * Return extra information.
     *
     * @return array|null
     */
    public function getAuditExtra(): ?array;

    /**
     * Transition to another model state from an Audit.
     *
     * @param Audit $audit
     * @param bool  $old
     *
     * @throws AuditableTransitionException
     *
     * @return Auditable
     */
    public function transitionTo(Audit $audit, bool $old = false): Auditable;
}
