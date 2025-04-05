describe('Testing Setup', () => {
  it('should run tests successfully', () => {
    expect(true).toBe(true);
  });

  it('should handle async operations', async () => {
    const result = await Promise.resolve(42);
    expect(result).toBe(42);
  });

  it('should handle type checking', () => {
    const numberArray: number[] = [1, 2, 3];
    expect(Array.isArray(numberArray)).toBe(true);
    expect(numberArray.every(n => typeof n === 'number')).toBe(true);
  });
}); 