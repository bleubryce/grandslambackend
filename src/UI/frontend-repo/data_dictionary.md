# Baseball Analytics System Data Dictionary

## Overview

This document provides a comprehensive data dictionary for the Baseball Analytics System, detailing the data elements, their definitions, formats, and relationships. This dictionary serves as a reference for understanding the data structure and semantics used throughout the system.

## Player Data

### Player Information

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| player_id | Unique identifier for a player | String | "aaronh001" |
| Name | Player's full name | String | "Aaron Judge" |
| Team | Current team abbreviation | String | "NYY" |
| Position | Primary playing position | String | "RF" |
| Age | Player's age in years | Integer | 30 |
| Height | Player's height in inches | Integer | 79 |
| Weight | Player's weight in pounds | Integer | 282 |
| Bats | Batting handedness (L/R/S) | String | "R" |
| Throws | Throwing handedness (L/R) | String | "R" |
| Birth_Date | Player's date of birth | Date | "1992-04-26" |
| Debut | MLB debut date | Date | "2016-08-13" |
| Draft_Year | Year player was drafted | Integer | 2013 |
| Draft_Round | Draft round | Integer | 1 |
| Draft_Position | Overall draft position | Integer | 32 |
| College | College attended | String | "Fresno State" |

## Batting Statistics

### Basic Batting Stats

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| Season | Year of the season | Integer | 2024 |
| Team | Team abbreviation | String | "NYY" |
| G | Games played | Integer | 148 |
| PA | Plate appearances | Integer | 620 |
| AB | At bats | Integer | 550 |
| H | Hits | Integer | 165 |
| 1B | Singles | Integer | 92 |
| 2B | Doubles | Integer | 28 |
| 3B | Triples | Integer | 3 |
| HR | Home runs | Integer | 42 |
| R | Runs scored | Integer | 110 |
| RBI | Runs batted in | Integer | 102 |
| SB | Stolen bases | Integer | 12 |
| CS | Caught stealing | Integer | 3 |
| BB | Walks | Integer | 80 |
| IBB | Intentional walks | Integer | 10 |
| SO | Strikeouts | Integer | 150 |
| HBP | Hit by pitch | Integer | 8 |
| SF | Sacrifice flies | Integer | 5 |
| SH | Sacrifice hits | Integer | 0 |
| GDP | Grounded into double play | Integer | 12 |

### Advanced Batting Metrics

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| AVG | Batting average (H/AB) | Float | .300 |
| OBP | On-base percentage | Float | .380 |
| SLG | Slugging percentage | Float | .550 |
| OPS | On-base plus slugging | Float | .930 |
| ISO | Isolated power (SLG-AVG) | Float | .250 |
| BABIP | Batting average on balls in play | Float | .320 |
| wOBA | Weighted on-base average | Float | .390 |
| wRC+ | Weighted runs created plus | Integer | 145 |
| BB% | Walk percentage (BB/PA) | Float | .129 |
| K% | Strikeout percentage (SO/PA) | Float | .242 |
| HR% | Home run percentage (HR/AB) | Float | .076 |
| WAR | Wins above replacement | Float | 5.8 |

## Pitching Statistics

### Basic Pitching Stats

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| Season | Year of the season | Integer | 2024 |
| Team | Team abbreviation | String | "NYY" |
| W | Wins | Integer | 15 |
| L | Losses | Integer | 7 |
| G | Games pitched | Integer | 32 |
| GS | Games started | Integer | 30 |
| CG | Complete games | Integer | 2 |
| ShO | Shutouts | Integer | 1 |
| SV | Saves | Integer | 0 |
| BS | Blown saves | Integer | 0 |
| IP | Innings pitched | Float | 180.2 |
| TBF | Total batters faced | Integer | 720 |
| H | Hits allowed | Integer | 150 |
| R | Runs allowed | Integer | 70 |
| ER | Earned runs allowed | Integer | 65 |
| HR | Home runs allowed | Integer | 20 |
| BB | Walks issued | Integer | 50 |
| IBB | Intentional walks issued | Integer | 5 |
| HBP | Hit batsmen | Integer | 8 |
| WP | Wild pitches | Integer | 6 |
| BK | Balks | Integer | 1 |
| SO | Strikeouts | Integer | 200 |

### Advanced Pitching Metrics

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| ERA | Earned run average | Float | 3.25 |
| FIP | Fielding independent pitching | Float | 3.40 |
| xFIP | Expected fielding independent pitching | Float | 3.35 |
| WHIP | Walks and hits per inning pitched | Float | 1.11 |
| BABIP | Batting average on balls in play against | Float | .290 |
| K/9 | Strikeouts per 9 innings | Float | 9.95 |
| BB/9 | Walks per 9 innings | Float | 2.49 |
| HR/9 | Home runs per 9 innings | Float | 0.99 |
| K/BB | Strikeout to walk ratio | Float | 4.00 |
| LOB% | Left on base percentage | Float | 75.0 |
| GB% | Ground ball percentage | Float | 45.0 |
| HR/FB | Home run to fly ball ratio | Float | 10.5 |
| WAR | Wins above replacement | Float | 4.2 |

## Statcast Data

### Pitch Data

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| game_date | Date of the game | Date | "2024-06-15" |
| player_name | Name of the pitcher | String | "Gerrit Cole" |
| batter | Name of the batter | String | "Rafael Devers" |
| pitch_type | Type of pitch thrown | String | "FF" |
| release_speed | Velocity of pitch at release (mph) | Float | 97.5 |
| release_pos_x | Horizontal release position (ft) | Float | -2.5 |
| release_pos_z | Vertical release position (ft) | Float | 6.2 |
| pfx_x | Horizontal movement (in) | Float | -6.5 |
| pfx_z | Vertical movement (in) | Float | 10.2 |
| plate_x | Horizontal position at plate (ft) | Float | 0.5 |
| plate_z | Vertical position at plate (ft) | Float | 2.3 |
| vx0 | Initial velocity in x-direction (ft/s) | Float | 6.2 |
| vy0 | Initial velocity in y-direction (ft/s) | Float | -135.5 |
| vz0 | Initial velocity in z-direction (ft/s) | Float | -9.8 |
| ax | Acceleration in x-direction (ft/s²) | Float | -10.5 |
| ay | Acceleration in y-direction (ft/s²) | Float | 25.2 |
| az | Acceleration in z-direction (ft/s²) | Float | -18.5 |
| spin_rate | Spin rate of pitch (rpm) | Float | 2450 |
| spin_dir | Spin direction (degrees) | Float | 210 |
| zone | Strike zone location (1-9, 11-14) | Integer | 5 |

### Batted Ball Data

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| events | Result of the plate appearance | String | "home_run" |
| description | Description of the play | String | "Home run (Fly Ball to Deep CF)" |
| launch_speed | Exit velocity (mph) | Float | 105.2 |
| launch_angle | Launch angle (degrees) | Float | 28.5 |
| hit_distance_sc | Projected hit distance (ft) | Float | 425 |
| hit_location | Field location (1-9) | Integer | 8 |
| hc_x | Hit coordinate x | Float | 125.5 |
| hc_y | Hit coordinate y | Float | 450.2 |
| hard_hit | Hard hit ball (exit velo >= 95 mph) | Boolean | 1 |
| barrel | Optimal launch angle and speed | Boolean | 1 |

## Team Data

### Team Information

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| Team | Team abbreviation | String | "NYY" |
| TeamName | Full team name | String | "New York Yankees" |
| League | League (AL/NL) | String | "AL" |
| Division | Division (East/Central/West) | String | "East" |
| Ballpark | Home ballpark | String | "Yankee Stadium" |
| Capacity | Ballpark capacity | Integer | 47309 |

### Team Performance

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| Season | Year of the season | Integer | 2024 |
| W | Wins | Integer | 92 |
| L | Losses | Integer | 70 |
| WPct | Winning percentage | Float | .568 |
| RS | Runs scored | Integer | 780 |
| RA | Runs allowed | Integer | 650 |
| Diff | Run differential | Integer | 130 |
| Rank | Division rank | Integer | 2 |
| Playoffs | Made playoffs (0/1) | Boolean | 1 |

## Model Data

### Model Metadata

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| model_id | Unique identifier for the model | String | "batting_war_rf_v1" |
| model_type | Type of model | String | "random_forest" |
| target | Target variable | String | "WAR" |
| created_date | Date model was created | Date | "2024-04-02" |
| version | Model version | String | "1.0" |
| r2_score | R-squared score | Float | 0.75 |
| rmse | Root mean squared error | Float | 0.85 |
| features | List of features used | Array | ["AVG", "OBP", "SLG", "HR%", "BB%", "K%"] |

### Feature Importance

| Field | Description | Data Type | Example |
|-------|-------------|-----------|---------|
| model_id | Model identifier | String | "batting_war_rf_v1" |
| feature | Feature name | String | "OBP" |
| importance | Feature importance score | Float | 0.35 |
| rank | Importance rank | Integer | 1 |

## Database Schema

The Baseball Analytics System uses a relational database with the following primary tables:

### Players Table
- Primary key: player_id
- Contains basic player information

### Teams Table
- Primary key: team_id
- Contains team information

### Batting_Stats Table
- Primary key: (player_id, season, team_id)
- Foreign keys: player_id, team_id
- Contains batting statistics

### Pitching_Stats Table
- Primary key: (player_id, season, team_id)
- Foreign keys: player_id, team_id
- Contains pitching statistics

### Statcast_Data Table
- Primary key: statcast_id
- Foreign keys: player_id (pitcher), batter_id
- Contains pitch and batted ball data

### Models Table
- Primary key: model_id
- Contains model metadata

### Predictions Table
- Primary key: prediction_id
- Foreign keys: model_id, player_id
- Contains model predictions

## Data Relationships

The database schema implements the following relationships:

1. One player can have multiple batting and pitching statistics records (one-to-many)
2. One team can have multiple players (one-to-many)
3. One pitcher can throw multiple pitches in Statcast data (one-to-many)
4. One model can generate multiple predictions (one-to-many)
5. Each batting/pitching statistic record belongs to one player and one team (many-to-one)

## Data Sources

The data in the system comes from the following sources:

1. **MLB Statcast**: Via pybaseball library
2. **Baseball Reference**: Via pybaseball library
3. **FanGraphs**: Via pybaseball library
4. **Chadwick Bureau**: Player identification and biographical data
5. **Team websites**: Additional team information
6. **Custom scouting data**: Internal scouting reports and evaluations

## Data Update Frequency

Different data elements are updated at different frequencies:

- **Game data**: Updated daily during the season
- **Player statistics**: Updated daily during the season
- **Statcast data**: Updated daily during the season
- **Team information**: Updated as needed (typically pre-season)
- **Model predictions**: Updated weekly or on-demand

## Data Quality Rules

The system enforces the following data quality rules:

1. All player_id values must be unique
2. Statistical values must be within reasonable ranges
3. Dates must be valid and in the correct format
4. Required fields cannot be null
5. Relationships between tables must maintain referential integrity
