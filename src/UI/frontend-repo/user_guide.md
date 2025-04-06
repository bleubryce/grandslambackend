# Baseball Analytics System User Guide

## Introduction

Welcome to the Baseball Analytics System! This comprehensive platform is designed to support Baseball Operations staff in data collection, analysis, modeling, and visualization of baseball-related data. This user guide will help you navigate the system's features and functionality.

## Getting Started

### System Requirements

- Python 3.10 or higher
- PostgreSQL database
- Required Python libraries (installed via pip):
  - pybaseball
  - pandas
  - numpy
  - scikit-learn
  - matplotlib
  - seaborn
  - dash
  - plotly
  - sqlalchemy
  - psycopg2-binary

### Installation

1. Clone the repository:
   ```
   git clone https://github.com/your-organization/baseball-analytics.git
   cd baseball-analytics
   ```

2. Install required dependencies:
   ```
   pip install -r requirements.txt
   ```

3. Set up the database:
   ```
   python scripts/setup_database.py
   ```

4. Collect initial data:
   ```
   python scripts/collect_data.py
   ```

5. Process and clean the data:
   ```
   python scripts/clean_data.py
   ```

6. Run analysis and modeling:
   ```
   python scripts/analysis_modeling.py
   ```

7. Launch the dashboard:
   ```
   python scripts/dashboard.py
   ```

## Data Collection

### Collecting Public Data

The system automatically collects data from public sources using the `collect_data.py` script. This includes:

- MLB Statcast data
- Player batting statistics
- Player pitching statistics
- Team performance data

To manually trigger data collection:

```
python scripts/collect_data.py
```

By default, this will collect data for the current season and the past 5 seasons. To collect data for specific years:

```
python scripts/collect_data.py --start-year 2015 --end-year 2025
```

### Scheduling Automated Collection

Set up a cron job to run the collection script automatically:

```
# Run daily at 5 AM
0 5 * * * cd /path/to/baseball_analytics && python scripts/collect_data.py
```

## Data Cleaning and Processing

### Running the Data Cleaning Pipeline

After collecting data, run the cleaning pipeline to prepare it for analysis:

```
python scripts/clean_data.py
```

This script:
- Handles missing values
- Converts data types
- Creates derived metrics
- Validates data quality
- Saves processed data to the `data/processed` directory

### Custom Data Transformations

To add custom transformations, edit the `clean_data.py` script and add your transformation functions.

## Analysis and Modeling

### Running Statistical Analysis

The system provides comprehensive statistical analysis capabilities:

```
python scripts/analysis_modeling.py
```

This generates:
- Basic statistical summaries
- Correlation analyses
- Player rankings
- Performance visualizations

Results are saved to the `reports` and `visualizations` directories.

### Player Evaluation Models

The system includes models for player evaluation:

- Batting WAR prediction model
- Pitching WAR prediction model
- Player similarity analysis

Model results are saved to the `models` directory and can be loaded for predictions.

### Custom Analysis

To perform custom analysis, use the provided classes in `analysis_modeling.py`:

```python
from scripts.analysis_modeling import StatisticalAnalysis, PlayerEvaluationModel, PlayerComparisonTool

# Create analysis object
analysis = StatisticalAnalysis()
analysis.load_all_data()

# Perform batting analysis
batting_results = analysis.batting_analysis(min_pa=200)

# Find similar players
comparison_tool = PlayerComparisonTool()
comparison_tool.load_data()
similar_players = comparison_tool.find_similar_batters(player_name="Aaron Judge")
```

## Dashboard and Visualization

### Launching the Dashboard

To start the interactive dashboard:

```
python scripts/dashboard.py
```

This will launch a web server at http://localhost:8050/

### Dashboard Features

The dashboard includes:

1. **Player Performance Tab**
   - Player performance metrics
   - Comparison to league averages
   - Similar player analysis

2. **Team Analysis Tab**
   - Team performance metrics
   - Team rankings
   - Historical team trends

3. **Statcast Analysis Tab**
   - Launch angle vs. launch speed visualization
   - Pitch type distribution
   - Pitch velocity and spin rate analysis

4. **Predictive Models Tab**
   - Model feature importance
   - WAR prediction tool
   - Model performance metrics

### Exporting Visualizations

To export visualizations from the dashboard:
1. Navigate to the desired visualization
2. Use the built-in export tools in the top-right corner of each graph
3. Select the desired format (PNG, JPEG, SVG, or PDF)

## Database Management

### Database Structure

The system uses a PostgreSQL database with the following main tables:
- Players
- Teams
- Batting_Stats
- Pitching_Stats
- Statcast_Data
- Models
- Predictions

For a complete schema, refer to the Data Dictionary document.

### Connecting to the Database

Use the following connection parameters:
- Host: localhost
- Port: 5432
- Database: baseball_analytics
- Username: postgres
- Password: baseball_analytics

Example connection using Python:

```python
from sqlalchemy import create_engine

DATABASE_URL = "postgresql://postgres:baseball_analytics@localhost:5432/baseball_analytics"
engine = create_engine(DATABASE_URL)
```

### Running Custom Queries

You can run custom SQL queries against the database:

```python
import pandas as pd
from sqlalchemy import create_engine, text

DATABASE_URL = "postgresql://postgres:baseball_analytics@localhost:5432/baseball_analytics"
engine = create_engine(DATABASE_URL)

query = """
SELECT p.Name, b.AVG, b.HR, b.RBI, b.OPS, b.WAR
FROM batting_stats b
JOIN players p ON b.player_id = p.player_id
WHERE b.Season = 2025
ORDER BY b.WAR DESC
LIMIT 10
"""

top_players = pd.read_sql(query, engine)
print(top_players)
```

## Reporting

### Generating Standard Reports

The system can generate standard reports:

```
python scripts/generate_reports.py --type season_summary --year 2025
```

Available report types:
- season_summary
- player_performance
- team_comparison
- draft_analysis
- free_agency_targets

### Customizing Reports

To customize reports, edit the templates in the `templates` directory or modify the report generation functions in `generate_reports.py`.

## Troubleshooting

### Common Issues

1. **Data Collection Errors**
   - Check internet connection
   - Verify API endpoints are accessible
   - Check for rate limiting issues

2. **Database Connection Issues**
   - Verify PostgreSQL is running
   - Check connection parameters
   - Ensure database user has appropriate permissions

3. **Dashboard Not Loading**
   - Check if port 8050 is available
   - Verify all required libraries are installed
   - Check for errors in the dashboard logs

### Logging

The system generates logs in the `logs` directory:
- `data_collection.log`
- `data_cleaning.log`
- `analysis_modeling.log`
- `dashboard.log`

Check these logs for detailed error information.

## Maintenance

### Backup Procedures

To back up the database:

```
pg_dump -U postgres -d baseball_analytics > backups/baseball_analytics_$(date +%Y%m%d).sql
```

Schedule regular backups using cron:

```
# Weekly backup on Sunday at 1 AM
0 1 * * 0 cd /path/to/baseball_analytics && pg_dump -U postgres -d baseball_analytics > backups/baseball_analytics_$(date +%Y%m%d).sql
```

### Updating the System

To update the system:

1. Pull the latest code:
   ```
   git pull origin main
   ```

2. Install any new dependencies:
   ```
   pip install -r requirements.txt
   ```

3. Run database migrations if needed:
   ```
   python scripts/migrate_database.py
   ```

### Monitoring

Monitor system health by:
- Checking log files regularly
- Setting up alerts for failed data collection jobs
- Monitoring database size and performance

## Support and Resources

For additional help:
- Refer to the system documentation in the `docs` directory
- Check the GitHub repository for updates and issues
- Contact the development team at baseball-analytics-support@your-organization.com

## Glossary

- **WAR**: Wins Above Replacement - A comprehensive statistic that measures a player's total contributions to their team
- **OPS**: On-base Plus Slugging - Sum of on-base percentage and slugging percentage
- **wRC+**: Weighted Runs Created Plus - Measures offensive value by runs created, adjusted for park and league
- **FIP**: Fielding Independent Pitching - Measures pitcher performance independent of fielding
- **Statcast**: MLB's tracking technology that measures player and ball movement
- **Barrel**: A batted ball with the optimal combination of exit velocity and launch angle
