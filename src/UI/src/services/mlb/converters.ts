
import { Player, Team, Game, PlayerStats, PitcherStats } from '../api';

// Convert MLB API player to our Player format
export const convertMlbPlayer = (mlbPlayer: any): Player => {
  // Extract batting and pitching stats if available
  const stats = mlbPlayer.stats || {};
  const battingStats = stats.batting || {};
  const pitchingStats = stats.pitching || {};
  
  return {
    id: mlbPlayer.id,
    firstName: mlbPlayer.firstName || mlbPlayer.useName || '',
    lastName: mlbPlayer.lastName || '',
    position: mlbPlayer.primaryPosition?.abbreviation || '',
    jerseyNumber: mlbPlayer.primaryNumber ? parseInt(mlbPlayer.primaryNumber) : 0,
    teamId: mlbPlayer.currentTeam?.id || 0,
    birthDate: mlbPlayer.birthDate || '',
    height: `${mlbPlayer.height || '6\'0"'}`,
    weight: mlbPlayer.weight || 180,
    bats: (mlbPlayer.batSide?.code?.toUpperCase() || 'R') as 'L' | 'R' | 'S',
    throws: (mlbPlayer.pitchHand?.code?.toUpperCase() || 'R') as 'L' | 'R',
    photoUrl: mlbPlayer.mlbHeadshotUrl || `https://img.mlbstatic.com/mlb-photos/image/upload/d_people:generic:headshot:67:current.png/w_213,q_auto:best/v1/people/${mlbPlayer.id}/headshot/67/current`,
    // Include statistical properties with default values
    battingAverage: battingStats.avg || 0,
    homeRuns: battingStats.homeRuns || 0,
    rbi: battingStats.rbi || 0,
    era: pitchingStats.era || 0,
    wins: pitchingStats.wins || 0,
    losses: pitchingStats.losses || 0,
    inningsPitched: pitchingStats.inningsPitched ? parseFloat(pitchingStats.inningsPitched) : 0,
    strikeouts: pitchingStats.strikeouts || battingStats.strikeouts || 0
  };
};

// Convert MLB API team to our Team format
export const convertMlbTeam = (mlbTeam: any): Team => {
  return {
    id: mlbTeam.id,
    name: mlbTeam.name || '',
    city: mlbTeam.locationName || '',
    mascot: mlbTeam.teamName || '',
    foundedYear: mlbTeam.firstYearOfPlay ? parseInt(mlbTeam.firstYearOfPlay) : 1900,
    logoUrl: `https://www.mlbstatic.com/team-logos/${mlbTeam.id}.svg`
  };
};

// Convert MLB API game to our Game format
export const convertMlbGame = (mlbGame: any): Game => {
  const homeTeam = mlbGame.teams?.home?.team || {};
  const awayTeam = mlbGame.teams?.away?.team || {};
  const gameDate = new Date(mlbGame.gameDate || mlbGame.officialDate);
  
  return {
    id: mlbGame.gamePk,
    date: gameDate.toISOString().split('T')[0],
    homeTeamId: homeTeam.id || 0,
    awayTeamId: awayTeam.id || 0,
    homeScore: mlbGame.teams?.home?.score,
    awayScore: mlbGame.teams?.away?.score,
    status: convertGameStatus(mlbGame.status?.abstractGameState || ''),
    location: mlbGame.venue?.name || '',
    startTime: mlbGame.gameDate || ''
  };
};

// Convert MLB game status to our status format
export const convertGameStatus = (status: string): 'scheduled' | 'in_progress' | 'completed' | 'postponed' | 'canceled' => {
  switch (status.toLowerCase()) {
    case 'final': return 'completed';
    case 'live': return 'in_progress';
    case 'preview': return 'scheduled';
    case 'postponed': return 'postponed';
    case 'cancelled': return 'canceled';
    default: return 'scheduled';
  }
};

// Get MLB player stats and convert to our format
export const convertPlayerStats = (statsData: any): PlayerStats => {
  const stats = statsData.stats || [];
  const battingStats = stats.find((s: any) => s.group === 'hitting')?.splits[0]?.stat || {};
  
  // Calculate rates
  const atBats = battingStats.atBats || 0;
  const hits = battingStats.hits || 0;
  const walks = battingStats.baseOnBalls || 0;
  const hbp = battingStats.hitByPitch || 0;
  const sf = battingStats.sacFlies || 0;
  const totalBases = (battingStats.singles || 0) + 2 * (battingStats.doubles || 0) + 
                    3 * (battingStats.triples || 0) + 4 * (battingStats.homeRuns || 0);
  
  // Avoid division by zero
  const battingAverage = atBats > 0 ? hits / atBats : 0;
  const onBasePercentage = (atBats + walks + hbp + sf) > 0 ? 
    (hits + walks + hbp) / (atBats + walks + hbp + sf) : 0;
  const sluggingPercentage = atBats > 0 ? totalBases / atBats : 0;

  return {
    playerId: statsData.id || 0,
    atBats: atBats,
    hits: hits,
    runs: battingStats.runs || 0,
    rbi: battingStats.rbi || 0,
    homeRuns: battingStats.homeRuns || 0,
    strikeouts: battingStats.strikeOuts || 0,
    walks: walks,
    stolenBases: battingStats.stolenBases || 0,
    battingAverage: parseFloat(battingAverage.toFixed(3)),
    onBasePercentage: parseFloat(onBasePercentage.toFixed(3)),
    sluggingPercentage: parseFloat(sluggingPercentage.toFixed(3))
  };
};

// Convert MLB pitcher stats to our format
export const convertPitcherStats = (statsData: any): PitcherStats => {
  const stats = statsData.stats || [];
  const pitchingStats = stats.find((s: any) => s.group === 'pitching')?.splits[0]?.stat || {};
  
  const inningsPitched = parseFloat(pitchingStats.inningsPitched || '0');
  const walks = pitchingStats.baseOnBalls || 0;
  const hits = pitchingStats.hits || 0;
  
  // Calculate WHIP (avoid division by zero)
  const whip = inningsPitched > 0 ? (walks + hits) / inningsPitched : 0;

  return {
    playerId: statsData.id || 0,
    wins: pitchingStats.wins || 0,
    losses: pitchingStats.losses || 0,
    era: pitchingStats.era || 0,
    games: pitchingStats.gamesPlayed || 0,
    gamesStarted: pitchingStats.gamesStarted || 0,
    inningsPitched: inningsPitched,
    strikeouts: pitchingStats.strikeOuts || 0,
    walks: walks,
    whip: parseFloat(whip.toFixed(2)),
    saves: pitchingStats.saves || 0,
    completeGames: pitchingStats.completeGames || 0
  };
};
