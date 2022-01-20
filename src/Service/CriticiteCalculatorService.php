<?php

namespace App\Service;

use App\Entity\Signalement;

class CriticiteCalculatorService
{
    private Signalement $signalement;

    public function __construct(Signalement $signalement)
    {
        $this->signalement = $signalement;
    }

    public function calculate(): float|int
    {
        $signalement = $this->signalement;
        $scoresMaxSituation = [];
        $scoreSituation = [];
        foreach ($signalement->getSituations() as $situation) {
            $scoresMaxSituation[$situation->getLabel()] = $scoreSituation[$situation->getLabel()] = 0;
            foreach ($situation->getCriteres() as $critere)
                foreach ($critere->getCriticites() as $criticite)
                    $scoresMaxSituation[$situation->getLabel()] += $criticite->getScore();
        }
        dd($scoresMaxSituation);
        foreach ($signalement->getCriticites() as $criticite)
            $scoreSituation[$criticite->getCritere()->getSituation()->getLabel()] += $criticite->getScore();
        $score = (array_sum($scoreSituation) / array_sum($scoresMaxSituation))*1000;
        if ($signalement->getNbEnfantsM6() || $signalement->getNbEnfantsP6())
            $score = $score * 1.1;
        return $score;
    }
}