<?php

namespace App\Console\Commands;

use App\Models\StudentCharge;
use App\Models\StudentSubscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:renew')]
#[Description('Generate renewal charges for subscriptions with automatic renewal enabled.')]
class RenewSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $created = 0;

        StudentSubscription::query()
            ->where('status', StudentSubscription::STATUS_ACTIVE)
            ->where('auto_renew', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now()->addDays(3))
            ->with(['student', 'network', 'unit'])
            ->chunkById(100, function ($subscriptions) use (&$created) {
                foreach ($subscriptions as $subscription) {
                    if (! $subscription->student || (float) $subscription->price <= 0) {
                        continue;
                    }

                    $alreadyPending = StudentCharge::query()
                        ->where('reference_type', 'subscription')
                        ->where('reference_id', $subscription->id)
                        ->where('status', StudentCharge::STATUS_PENDING)
                        ->where('description', 'like', 'Renovação%')
                        ->exists();

                    if ($alreadyPending) {
                        continue;
                    }

                    StudentCharge::create([
                        'network_id' => $subscription->network_id,
                        'unit_id' => $subscription->unit_id,
                        'student_id' => $subscription->student_id,
                        'type' => StudentCharge::TYPE_PLAN,
                        'description' => 'Renovação do plano '.$subscription->plan_name,
                        'amount' => $subscription->price,
                        'status' => StudentCharge::STATUS_PENDING,
                        'reference_type' => 'subscription',
                        'reference_id' => $subscription->id,
                        'due_date' => ($subscription->ends_at ?? today())->toDateString(),
                        'metadata' => [
                            'plan_type' => $subscription->plan_type,
                            'period' => $subscription->period,
                            'renewal' => true,
                        ],
                    ]);

                    $created++;
                }
            });

        $this->info("Renovações geradas: {$created}.");

        return self::SUCCESS;
    }
}
