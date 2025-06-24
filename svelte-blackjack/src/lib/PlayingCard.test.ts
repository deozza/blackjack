import { render, screen } from '@testing-library/svelte';
import { describe, it, expect } from 'vitest';
import PlayingCard from './PlayingCard.svelte';

describe('Composant PlayingCard', () => {
  it('doit afficher une carte avec la couleur et valeur correctes', () => {
    render(PlayingCard, {
      props: {
        suit: 'hearts',
        value: 'A'
      }
    });

    // Vérifie que la valeur de la carte est affichée
    expect(screen.getByText('A')).toBeInTheDocument();

    // Vérifie que l'icône hearts est présente
    const heartIcon = document.querySelector('[icon="bi:suit-hearts-fill"]');
    expect(heartIcon).toBeInTheDocument();
  });

  it('doit gérer différentes valeurs de cartes', () => {
    const testCases = [
      { suit: 'spades', value: 'K' },
      { suit: 'diamonds', value: '10' },
      { suit: 'clubs', value: 'J' },
      { suit: 'hearts', value: 'Q' }
    ];

    testCases.forEach(({ suit, value }) => {
      const { unmount } = render(PlayingCard, {
        props: { suit, value }
      });

      // Test basique que le composant se rend sans erreur
      expect(document.body.firstChild).toBeTruthy();

      unmount();
    });
  });

  it('doit gérer les valeurs numériques', () => {
    const { unmount } = render(PlayingCard, {
      props: {
        suit: 'hearts',
        value: '7'
      }
    });

    expect(document.body.firstChild).toBeTruthy();

    unmount();
  });
}); 