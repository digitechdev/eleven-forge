/** Forge accent tones, built on the chart colours in app.css. */
export type Tone =
    | 'ember'
    | 'steel'
    | 'brass'
    | 'graphite'
    | 'patina'
    | 'danger';

export const toneIcon: Record<Tone, string> = {
    ember: 'bg-chart-1/12 text-chart-1',
    steel: 'bg-chart-2/12 text-chart-2',
    brass: 'bg-chart-3/15 text-chart-3',
    graphite: 'bg-chart-4/12 text-chart-4',
    patina: 'bg-chart-5/12 text-chart-5',
    danger: 'bg-destructive/12 text-destructive',
};

export const toneBar: Record<Tone, string> = {
    ember: 'bg-chart-1',
    steel: 'bg-chart-2',
    brass: 'bg-chart-3',
    graphite: 'bg-chart-4',
    patina: 'bg-chart-5',
    danger: 'bg-destructive',
};
