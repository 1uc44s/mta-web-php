<?php

declare(strict_types=1);
use Tests\TestCase;

/*
 * Pest yapılandırması. Test adları gereksinim kimliğiyle başlar: it('FR-SUP-006: …') (docs/RULES.md §11).
 */

pest()->extend(TestCase::class)->in('Feature', 'Unit');
