"""
Data collection script for Baseball Analytics System
This script collects data from various public baseball data sources using pybaseball.
"""

import os
import sys
import pandas as pd
import numpy as np
import pybaseball as pyb
from datetime import datetime, timedelta
import sqlalchemy as sa
from sqlalchemy import create_engine, text
import logging

# Set up logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("logs/data_collection.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Database connection settings
DB_USER = "postgres"
DB_PASSWORD = "baseball_analytics"
DB_HOST = "localhost"
DB_PORT = "5432"
DB_NAME = "baseball_analytics"

# Create the database URL
DATABASE_URL = f"postgresql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{DB_NAME}"

# Create data directories if they don't exist
os.makedirs('data/raw', exist_ok=True)
os.makedirs('data/processed', exist_ok=True)
os.makedirs('logs', exist_ok=True)

def connect_to_db():
    """Connect to the database"""
    try:
        engine = create_engine(DATABASE_URL)
        return engine
    except Exception as e:
        logger.error(f"Error connecting to database: {e}")
        sys.exit(1)

def get_statcast_data(start_date, end_date):
    """
    Collect Statcast data for a given date range
    
    Args:
        start_date (str): Start date in YYYY-MM-DD format
        end_date (str): End date in YYYY-MM-DD format
        
    Returns:
        pandas.DataFrame: Statcast data
    """
    try:
        logger.info(f"Collecting Statcast data from {start_date} to {end_date}")
        data = pyb.statcast(start_dt=start_date, end_dt=end_date)
        logger.info(f"Collected {len(data)} Statcast records")
        return data
    except Exception as e:
        logger.error(f"Error collecting Statcast data: {e}")
        return pd.DataFrame()

def get_batting_stats(season):
    """
    Collect batting statistics for a given season
    
    Args:
        season (int): MLB season year
        
    Returns:
        pandas.DataFrame: Batting statistics
    """
    try:
        logger.info(f"Collecting batting stats for {season} season")
        data = pyb.batting_stats(season)
        logger.info(f"Collected batting stats for {len(data)} players")
        return data
    except Exception as e:
        logger.error(f"Error collecting batting stats: {e}")
        return pd.DataFrame()

def get_pitching_stats(season):
    """
    Collect pitching statistics for a given season
    
    Args:
        season (int): MLB season year
        
    Returns:
        pandas.DataFrame: Pitching statistics
    """
    try:
        logger.info(f"Collecting pitching stats for {season} season")
        data = pyb.pitching_stats(season)
        logger.info(f"Collected pitching stats for {len(data)} players")
        return data
    except Exception as e:
        logger.error(f"Error collecting pitching stats: {e}")
        return pd.DataFrame()

def get_team_data(season):
    """
    Collect team data for a given season
    
    Args:
        season (int): MLB season year
        
    Returns:
        pandas.DataFrame: Team data
    """
    try:
        logger.info(f"Collecting team data for {season} season")
        data = pyb.team_batting(season)
        logger.info(f"Collected data for {len(data)} teams")
        return data
    except Exception as e:
        logger.error(f"Error collecting team data: {e}")
        return pd.DataFrame()

def save_to_csv(data, filename):
    """
    Save data to CSV file
    
    Args:
        data (pandas.DataFrame): Data to save
        filename (str): Filename to save to
    """
    try:
        filepath = os.path.join('data/raw', filename)
        data.to_csv(filepath, index=False)
        logger.info(f"Saved data to {filepath}")
    except Exception as e:
        logger.error(f"Error saving data to {filename}: {e}")

def save_to_database(data, table_name, engine, if_exists='append'):
    """
    Save data to database
    
    Args:
        data (pandas.DataFrame): Data to save
        table_name (str): Table name to save to
        engine (sqlalchemy.engine.Engine): Database engine
        if_exists (str): How to behave if the table already exists
    """
    try:
        data.to_sql(table_name, engine, if_exists=if_exists, index=False)
        logger.info(f"Saved {len(data)} records to {table_name} table")
    except Exception as e:
        logger.error(f"Error saving data to {table_name} table: {e}")

def collect_player_ids():
    """
    Collect player IDs from various sources
    
    Returns:
        pandas.DataFrame: Player IDs
    """
    try:
        logger.info("Collecting player IDs")
        data = pyb.chadwick_register()
        logger.info(f"Collected {len(data)} player IDs")
        return data
    except Exception as e:
        logger.error(f"Error collecting player IDs: {e}")
        return pd.DataFrame()

def collect_recent_data():
    """Collect recent data from the past week"""
    today = datetime.today()
    week_ago = today - timedelta(days=7)
    
    today_str = today.strftime('%Y-%m-%d')
    week_ago_str = week_ago.strftime('%Y-%m-%d')
    current_year = today.year
    
    # Connect to database
    engine = connect_to_db()
    
    # Collect and save player IDs
    player_ids = collect_player_ids()
    if not player_ids.empty:
        save_to_csv(player_ids, 'player_ids.csv')
    
    # Collect and save Statcast data
    statcast_data = get_statcast_data(week_ago_str, today_str)
    if not statcast_data.empty:
        save_to_csv(statcast_data, f'statcast_{week_ago_str}_to_{today_str}.csv')
        # Process for database - would need more complex mapping to match schema
    
    # Collect and save batting stats
    batting_stats = get_batting_stats(current_year)
    if not batting_stats.empty:
        save_to_csv(batting_stats, f'batting_stats_{current_year}.csv')
        # Process for database - would need more complex mapping to match schema
    
    # Collect and save pitching stats
    pitching_stats = get_pitching_stats(current_year)
    if not pitching_stats.empty:
        save_to_csv(pitching_stats, f'pitching_stats_{current_year}.csv')
        # Process for database - would need more complex mapping to match schema
    
    # Collect and save team data
    team_data = get_team_data(current_year)
    if not team_data.empty:
        save_to_csv(team_data, f'team_data_{current_year}.csv')
        # Process for database - would need more complex mapping to match schema

def collect_historical_data(start_year, end_year):
    """
    Collect historical data for a range of years
    
    Args:
        start_year (int): Start year
        end_year (int): End year
    """
    # Connect to database
    engine = connect_to_db()
    
    for year in range(start_year, end_year + 1):
        # Collect and save batting stats
        batting_stats = get_batting_stats(year)
        if not batting_stats.empty:
            save_to_csv(batting_stats, f'batting_stats_{year}.csv')
        
        # Collect and save pitching stats
        pitching_stats = get_pitching_stats(year)
        if not pitching_stats.empty:
            save_to_csv(pitching_stats, f'pitching_stats_{year}.csv')
        
        # Collect and save team data
        team_data = get_team_data(year)
        if not team_data.empty:
            save_to_csv(team_data, f'team_data_{year}.csv')

def main():
    """Main function to collect baseball data"""
    try:
        # Create log directory if it doesn't exist
        os.makedirs('logs', exist_ok=True)
        
        # Collect recent data
        logger.info("Starting recent data collection")
        collect_recent_data()
        logger.info("Recent data collection completed")
        
        # Collect historical data for the past 5 years
        current_year = datetime.today().year
        start_year = current_year - 5
        logger.info(f"Starting historical data collection from {start_year} to {current_year}")
        collect_historical_data(start_year, current_year)
        logger.info("Historical data collection completed")
        
    except Exception as e:
        logger.error(f"Error in data collection: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
