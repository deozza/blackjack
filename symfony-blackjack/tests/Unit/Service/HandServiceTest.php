<?php


namespace App\Tests\Unit\Service;

use App\Entity\Card;
use App\Entity\Hand;
use App\Service\HandService;
use PHPUnit\Framework\TestCase;

class HandServiceTest extends TestCase
{
    private HandService $handService;

    protected function setUp(): void
    {
        $this->handService = new HandService();
    }

    public function testCalculateScoreWithNumericCards(): void
    {
        $hand = new Hand();
        $hand->addCard(new Card('hearts', '7'));
        $hand->addCard(new Card('clubs', '8'));
        
        [$result, $error] = $this->handService->calculateScore($hand);
        
        $this->assertInstanceOf(Hand::class, $result);
        $this->assertNull($error);
        $this->assertEquals(15, $result->getScore());
        $this->assertFalse($result->getIsBlackjack());
        $this->assertFalse($result->getIsBusted());
    }

    public function testCalculateScoreWithFaceCards(): void
    {
        $hand = new Hand();
        $hand->addCard(new Card('hearts', 'jack'));
        $hand->addCard(new Card('clubs', 'king'));
        
        [$result, $error] = $this->handService->calculateScore($hand);
        
        $this->assertInstanceOf(Hand::class, $result);
        $this->assertNull($error);
        $this->assertEquals(20, $result->getScore());
        $this->assertFalse($result->getIsBlackjack());
        $this->assertFalse($result->getIsBusted());
    }

    public function testCalculateScoreWithAceCountingAs11(): void
    {
        $hand = new Hand();
        $hand->addCard(new Card('hearts', 'ace'));
        $hand->addCard(new Card('clubs', '7'));
        
        [$result, $error] = $this->handService->calculateScore($hand);
        
        $this->assertInstanceOf(Hand::class, $result);
        $this->assertNull($error);
        $this->assertEquals(18, $result->getScore());
        $this->assertFalse($result->getIsBlackjack());
        $this->assertFalse($result->getIsBusted());
    }

    public function testCalculateScoreWithAceCountingAs1(): void
    {
        $hand = new Hand();
        $hand->addCard(new Card('hearts', 'ace'));
        $hand->addCard(new Card('clubs', 'king'));
        $hand->addCard(new Card('diamonds', '5'));
        
        [$result, $error] = $this->handService->calculateScore($hand);
        
        $this->assertInstanceOf(Hand::class, $result);
        $this->assertNull($error);
        $this->assertEquals(16, $result->getScore()); // A compte pour 1 + K (10) + 5 = 16
        $this->assertFalse($result->getIsBlackjack());
        $this->assertFalse($result->getIsBusted());
    }

    public function testCalculateScoreWithBlackjack(): void
    {
        $hand = new Hand();
        $hand->addCard(new Card('hearts', 'ace'));
        $hand->addCard(new Card('clubs', 'king'));
        
        [$result, $error] = $this->handService->calculateScore($hand);
        
        $this->assertInstanceOf(Hand::class, $result);
        $this->assertNull($error);
        $this->assertEquals(21, $result->getScore());
        $this->assertTrue($result->getIsBlackjack());
        $this->assertFalse($result->getIsBusted());
    }

    public function testCalculateScoreWithBustedHand(): void
    {
        $hand = new Hand();
        $hand->addCard(new Card('hearts', 'king'));
        $hand->addCard(new Card('clubs', 'queen'));
        $hand->addCard(new Card('diamonds', '5'));
        
        [$result, $error] = $this->handService->calculateScore($hand);
        
        $this->assertInstanceOf(Hand::class, $result);
        $this->assertNull($error);
        $this->assertEquals(25, $result->getScore()); // K (10) + Q (10) + 5 = 25
        $this->assertFalse($result->getIsBlackjack());
        $this->assertTrue($result->getIsBusted());
    }

    public function testCalculateScoreWith21ButNotBlackjack(): void
    {
        $hand = new Hand();
        $hand->addCard(new Card('hearts', '5'));
        $hand->addCard(new Card('clubs', '6'));
        $hand->addCard(new Card('diamonds', '10'));
        
        [$result, $error] = $this->handService->calculateScore($hand);
        
        $this->assertInstanceOf(Hand::class, $result);
        $this->assertNull($error);
        $this->assertEquals(21, $result->getScore());
        $this->assertFalse($result->getIsBlackjack()); // 3 cartes, donc ce n'est pas un blackjack
        $this->assertFalse($result->getIsBusted());
    }
}