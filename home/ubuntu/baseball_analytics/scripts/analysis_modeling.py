"""
Analysis and modeling framework for Baseball Analytics System
This script provides statistical analysis and modeling capabilities for baseball data.
"""

import os
import sys
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns
from datetime import datetime
import sqlalchemy as sa
from sqlalchemy import create_engine, text
import logging
import glob
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
from sklearn.linear_model import LinearRegression, Ridge, Lasso
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.metrics import mean_squared_error, r2_score, mean_absolute_error
import joblib

# Set up logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("logs/analysis_modeling.log"),
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

# Create directories if they don't exist
os.makedirs('models', exist_ok=True)
os.makedirs('reports', exist_ok=True)
os.makedirs('visualizations', exist_ok=True)
os.makedirs('logs', exist_ok=True)

def connect_to_db():
    """Connect to the database"""
    try:
        engine = create_engine(DATABASE_URL)
        return engine
    except Exception as e:
        logger.error(f"Error connecting to database: {e}")
        sys.exit(1)

def load_data(data_type, year=None):
    """
    Load cleaned data for analysis
    
    Args:
        data_type (str): Type of data to load ('batting', 'pitching', 'team', 'statcast')
        year (int, optional): Specific year to load. If None, loads all years.
        
    Returns:
        pandas.DataFrame: Loaded data
    """
    try:
        if year:
            # Load specific year
            file_pattern = f'data/processed/clean_{data_type}_stats_{year}.csv'
            if data_type == 'statcast':
                # Statcast files have different naming pattern
                file_pattern = f'data/processed/clean_statcast_*_{year}*.csv'
            
            files = glob.glob(file_pattern)
            if not files:
                logger.warning(f"No {data_type} data files found for year {year}")
                return pd.DataFrame()
            
            data = pd.read_csv(files[0])
            logger.info(f"Loaded {data_type} data for {year}: {len(data)} records")
            
        else:
            # Load all years
            file_pattern = f'data/processed/clean_{data_type}_stats_*.csv'
            if data_type == 'statcast':
                file_pattern = 'data/processed/clean_statcast_*.csv'
            
            files = glob.glob(file_pattern)
            if not files:
                logger.warning(f"No {data_type} data files found")
                return pd.DataFrame()
            
            data_frames = []
            for file in files:
                df = pd.read_csv(file)
                data_frames.append(df)
            
            data = pd.concat(data_frames, ignore_index=True)
            logger.info(f"Loaded {data_type} data for all years: {len(data)} records")
        
        return data
    
    except Exception as e:
        logger.error(f"Error loading {data_type} data: {e}")
        return pd.DataFrame()

class StatisticalAnalysis:
    """Class for statistical analysis of baseball data"""
    
    def __init__(self):
        """Initialize the statistical analysis class"""
        self.batting_data = None
        self.pitching_data = None
        self.team_data = None
        self.statcast_data = None
    
    def load_all_data(self):
        """Load all data for analysis"""
        self.batting_data = load_data('batting')
        self.pitching_data = load_data('pitching')
        self.team_data = load_data('team')
        self.statcast_data = load_data('statcast')
    
    def batting_analysis(self, min_pa=100):
        """
        Perform statistical analysis on batting data
        
        Args:
            min_pa (int): Minimum plate appearances to include
            
        Returns:
            dict: Dictionary of analysis results
        """
        if self.batting_data is None:
            self.batting_data = load_data('batting')
        
        if self.batting_data.empty:
            logger.warning("No batting data available for analysis")
            return {}
        
        # Filter for minimum plate appearances
        if 'PA' in self.batting_data.columns:
            filtered_data = self.batting_data[self.batting_data['PA'] >= min_pa]
        else:
            filtered_data = self.batting_data
        
        results = {}
        
        # Basic statistics
        numeric_cols = filtered_data.select_dtypes(include=[np.number]).columns.tolist()
        results['basic_stats'] = filtered_data[numeric_cols].describe()
        
        # Correlation analysis
        key_metrics = ['AVG', 'OBP', 'SLG', 'OPS', 'HR', 'BB%', 'K%', 'wRC+', 'WAR']
        key_metrics = [col for col in key_metrics if col in filtered_data.columns]
        
        if key_metrics:
            results['correlations'] = filtered_data[key_metrics].corr()
        
        # Top performers by WAR
        if 'WAR' in filtered_data.columns and 'Name' in filtered_data.columns:
            results['top_war'] = filtered_data.sort_values('WAR', ascending=False).head(10)[['Name', 'Team', 'Season', 'WAR']]
        
        # Top power hitters (HR rate)
        if 'HR%' in filtered_data.columns and 'Name' in filtered_data.columns:
            results['top_power'] = filtered_data.sort_values('HR%', ascending=False).head(10)[['Name', 'Team', 'Season', 'HR%']]
        
        # Top plate discipline (BB/K ratio)
        if all(col in filtered_data.columns for col in ['BB', 'SO', 'Name']):
            filtered_data['BB_K_ratio'] = filtered_data['BB'] / filtered_data['SO'].replace(0, 0.001)
            results['top_discipline'] = filtered_data.sort_values('BB_K_ratio', ascending=False).head(10)[['Name', 'Team', 'Season', 'BB', 'SO', 'BB_K_ratio']]
        
        return results
    
    def pitching_analysis(self, min_ip=30):
        """
        Perform statistical analysis on pitching data
        
        Args:
            min_ip (int): Minimum innings pitched to include
            
        Returns:
            dict: Dictionary of analysis results
        """
        if self.pitching_data is None:
            self.pitching_data = load_data('pitching')
        
        if self.pitching_data.empty:
            logger.warning("No pitching data available for analysis")
            return {}
        
        # Filter for minimum innings pitched
        if 'IP' in self.pitching_data.columns:
            filtered_data = self.pitching_data[self.pitching_data['IP'] >= min_ip]
        else:
            filtered_data = self.pitching_data
        
        results = {}
        
        # Basic statistics
        numeric_cols = filtered_data.select_dtypes(include=[np.number]).columns.tolist()
        results['basic_stats'] = filtered_data[numeric_cols].describe()
        
        # Correlation analysis
        key_metrics = ['ERA', 'FIP', 'xFIP', 'WHIP', 'K/9', 'BB/9', 'HR/9', 'WAR']
        key_metrics = [col for col in key_metrics if col in filtered_data.columns]
        
        if key_metrics:
            results['correlations'] = filtered_data[key_metrics].corr()
        
        # Top performers by WAR
        if 'WAR' in filtered_data.columns and 'Name' in filtered_data.columns:
            results['top_war'] = filtered_data.sort_values('WAR', ascending=False).head(10)[['Name', 'Team', 'Season', 'WAR']]
        
        # Top strikeout pitchers
        if 'K/9' in filtered_data.columns and 'Name' in filtered_data.columns:
            results['top_strikeout'] = filtered_data.sort_values('K/9', ascending=False).head(10)[['Name', 'Team', 'Season', 'K/9']]
        
        # Best control pitchers (lowest BB/9)
        if 'BB/9' in filtered_data.columns and 'Name' in filtered_data.columns:
            results['top_control'] = filtered_data.sort_values('BB/9').head(10)[['Name', 'Team', 'Season', 'BB/9']]
        
        return results
    
    def statcast_analysis(self):
        """
        Perform statistical analysis on Statcast data
        
        Returns:
            dict: Dictionary of analysis results
        """
        if self.statcast_data is None:
            self.statcast_data = load_data('statcast')
        
        if self.statcast_data.empty:
            logger.warning("No Statcast data available for analysis")
            return {}
        
        results = {}
        
        # Basic statistics for key metrics
        key_metrics = ['launch_speed', 'launch_angle', 'release_speed', 'spin_rate']
        key_metrics = [col for col in key_metrics if col in self.statcast_data.columns]
        
        if key_metrics:
            results['basic_stats'] = self.statcast_data[key_metrics].describe()
        
        # Pitch type distribution
        if 'pitch_type' in self.statcast_data.columns:
            results['pitch_distribution'] = self.statcast_data['pitch_type'].value_counts().to_dict()
        
        # Average launch speed and angle by pitch type
        if all(col in self.statcast_data.columns for col in ['pitch_type', 'launch_speed', 'launch_angle']):
            results['launch_by_pitch'] = self.statcast_data.groupby('pitch_type')[['launch_speed', 'launch_angle']].mean().to_dict()
        
        # Hard hit rate by pitch type
        if all(col in self.statcast_data.columns for col in ['pitch_type', 'hard_hit']):
            results['hard_hit_rate'] = self.statcast_data.groupby('pitch_type')['hard_hit'].mean().to_dict()
        
        return results
    
    def generate_visualizations(self, output_dir='visualizations'):
        """
        Generate visualizations from the analysis
        
        Args:
            output_dir (str): Directory to save visualizations
        """
        try:
            # Ensure output directory exists
            os.makedirs(output_dir, exist_ok=True)
            
            # Batting visualizations
            if self.batting_data is not None and not self.batting_data.empty:
                # Distribution of key batting metrics
                key_metrics = ['AVG', 'OBP', 'SLG', 'OPS', 'wRC+', 'WAR']
                key_metrics = [col for col in key_metrics if col in self.batting_data.columns]
                
                if key_metrics:
                    plt.figure(figsize=(15, 10))
                    for i, metric in enumerate(key_metrics):
                        plt.subplot(2, 3, i+1)
                        sns.histplot(self.batting_data[metric].dropna(), kde=True)
                        plt.title(f'Distribution of {metric}')
                    
                    plt.tight_layout()
                    plt.savefig(f'{output_dir}/batting_metrics_distribution.png')
                    plt.close()
                    logger.info(f"Saved batting metrics distribution visualization to {output_dir}/batting_metrics_distribution.png")
                
                # Correlation heatmap
                if len(key_metrics) > 1:
                    plt.figure(figsize=(12, 10))
                    sns.heatmap(self.batting_data[key_metrics].corr(), annot=True, cmap='coolwarm')
                    plt.title('Correlation Between Batting Metrics')
                    plt.tight_layout()
                    plt.savefig(f'{output_dir}/batting_correlation_heatmap.png')
                    plt.close()
                    logger.info(f"Saved batting correlation heatmap to {output_dir}/batting_correlation_heatmap.png")
            
            # Pitching visualizations
            if self.pitching_data is not None and not self.pitching_data.empty:
                # Distribution of key pitching metrics
                key_metrics = ['ERA', 'FIP', 'WHIP', 'K/9', 'BB/9', 'WAR']
                key_metrics = [col for col in key_metrics if col in self.pitching_data.columns]
                
                if key_metrics:
                    plt.figure(figsize=(15, 10))
                    for i, metric in enumerate(key_metrics):
                        plt.subplot(2, 3, i+1)
                        sns.histplot(self.pitching_data[metric].dropna(), kde=True)
                        plt.title(f'Distribution of {metric}')
                    
                    plt.tight_layout()
                    plt.savefig(f'{output_dir}/pitching_metrics_distribution.png')
                    plt.close()
                    logger.info(f"Saved pitching metrics distribution visualization to {output_dir}/pitching_metrics_distribution.png")
                
                # Correlation heatmap
                if len(key_metrics) > 1:
                    plt.figure(figsize=(12, 10))
                    sns.heatmap(self.pitching_data[key_metrics].corr(), annot=True, cmap='coolwarm')
                    plt.title('Correlation Between Pitching Metrics')
                    plt.tight_layout()
                    plt.savefig(f'{output_dir}/pitching_correlation_heatmap.png')
                    plt.close()
                    logger.info(f"Saved pitching correlation heatmap to {output_dir}/pitching_correlation_heatmap.png")
            
            # Statcast visualizations
            if self.statcast_data is not None and not self.statcast_data.empty:
                # Launch angle vs. launch speed
                if all(col in self.statcast_data.columns for col in ['launch_angle', 'launch_speed']):
                    plt.figure(figsize=(10, 8))
                    sns.scatterplot(data=self.statcast_data.sample(min(5000, len(self.statcast_data))), 
                                   x='launch_angle', y='launch_speed', alpha=0.5)
                    plt.title('Launch Angle vs. Launch Speed')
                    plt.xlabel('Launch Angle (degrees)')
                    plt.ylabel('Launch Speed (mph)')
                    plt.tight_layout()
                    plt.savefig(f'{output_dir}/launch_angle_vs_speed.png')
                    plt.close()
                    logger.info(f"Saved launch angle vs. speed visualization to {output_dir}/launch_angle_vs_speed.png")
                
                # Pitch type distribution
                if 'pitch_type' in self.statcast_data.columns:
                    plt.figure(figsize=(12, 8))
                    sns.countplot(data=self.statcast_data, x='pitch_type', order=self.statcast_data['pitch_type'].value_counts().index)
                    plt.title('Pitch Type Distribution')
                    plt.xticks(rotation=45)
                    plt.tight_layout()
                    plt.savefig(f'{output_dir}/pitch_type_distribution.png')
                    plt.close()
                    logger.info(f"Saved pitch type distribution visualization to {output_dir}/pitch_type_distribution.png")
            
        except Exception as e:
            logger.error(f"Error generating visualizations: {e}")

class PlayerEvaluationModel:
    """Class for player evaluation models"""
    
    def __init__(self):
        """Initialize the player evaluation model class"""
        self.batting_data = None
        self.pitching_data = None
        self.models = {}
    
    def load_data(self):
        """Load data for modeling"""
        self.batting_data = load_data('batting')
        self.pitching_data = load_data('pitching')
    
    def prepare_batting_features(self, min_pa=100):
        """
        Prepare features for batting models
        
        Args:
            min_pa (int): Minimum plate appearances to include
            
      
(Content truncated due to size limit. Use line ranges to read in chunks)