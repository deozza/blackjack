<?php


namespace App\Service;

use App\Entity\Card;
use App\Entity\Hand;

class HandService
{
    /**
     * Calculate the score of a hand
     * @param Hand $hand
     * @return array [Hand, ?Error]
     */
    public function calculateScore(Hand $hand): array
    {
        try {
            $cards = $hand->getCards();
            $score = 0;
            $aceCount = 0;
            
            // Premier passage : calculer le score de base et compter les as
            foreach ($cards as $card) {
                $value = $card->getValue();
                
                // Gestion des as (A ou ace)
                if ($value === 'ace' || $value === 'A') {
                    $aceCount++;
                } 
                // Gestion des figures (J, Q, K, jack, queen, king)
                else if (in_array($value, ['J', 'Q', 'K', 'jack', 'queen', 'king'])) {
                    $score += 10;
                } 
                // Gestion des cartes numériques
                else {
                    $score += (int)$value;
                }
            }
            
            // Deuxième passage : traiter les as
            // Par défaut on compte les as comme 11, sauf si ça fait dépasser 21
            for ($i = 0; $i < $aceCount; $i++) {
                if ($score + 11 <= 21) {
                    $score += 11;
                } else {
                    $score += 1;
                }
            }
            
            // Mettre à jour le score de la main
            $hand->setScore($score);
            
            // Vérifier si c'est un blackjack (21 points avec exactement 2 cartes)
            $isBlackjack = ($score === 21 && count($cards) === 2);
            $hand->setIsBlackjack($isBlackjack);
            
            // Vérifier si la main est "busted" (>21)
            $isBusted = ($score > 21);
            $hand->setIsBusted($isBusted);
            
            return [$hand, null];
        } catch (\Exception $e) {
            return [null, new \Error('Error calculating score: ' . $e->getMessage(), 500)];
        }
    }
}