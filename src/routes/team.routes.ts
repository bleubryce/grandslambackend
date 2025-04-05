import { Router } from 'express';
import { PrismaClient, Prisma } from '@prisma/client';

const router = Router();
const prisma = new PrismaClient();

// Get all teams
router.get('/', async (req, res) => {
  try {
    const teams = await prisma.team.findMany({
      include: {
        players: true
      }
    });
    res.json(teams);
  } catch (error) {
    console.error('Error fetching teams:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create a team
router.post('/', async (req, res) => {
  try {
    const { name, city } = req.body;
    
    if (!name || !city) {
      return res.status(400).json({ error: 'Name and city are required' });
    }

    const team = await prisma.team.create({
      data: {
        name,
        city
      }
    });
    
    res.status(201).json(team);
  } catch (error) {
    console.error('Error creating team:', error);
    if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2002') {
      return res.status(409).json({ error: 'Team name already exists' });
    }
    res.status(500).json({ error: 'Internal server error' });
  }
});

export default router; 