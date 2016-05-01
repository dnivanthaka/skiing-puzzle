#!/usr/bin/env python3
import csv
#from os import remove, close

class NodeProcessor:
  
  
  def __init__(self, ifile, ofile):
    self.dimensions = []
    self.data = []
    self.iFile = ifile
    self.oFileName = ofile
    self.oFile = open(ofile, 'w')
    self.templines = ''
    self.currentVal = -1
    

  #Reads the input data file  
  def readInputData(self):
    with open(self.iFile, 'r') as csvfile:
      #fp = csv.reader(csvfile, delimiter=' ')
  
      #for line in fp:
        #self.data.append(line)
      self.data = [list(map(int,rec)) for rec in csv.reader(csvfile, delimiter=' ')]
      
    self.dimensions = self.data[0]
    del(self.data[0])
    
  def getMaxDataValue(self):
    res = []
    maxval = 0
  
    y = 0
    for row in self.data:
      x = 0
      for val in row:
        if int(maxval) < int(val) :
          maxval = val
          res = [int(maxval), y, x]
        x = x + 1
      y = y + 1
    return res
  
  #Find specific values in the data array
  def findValues(self, val):
    res = [(index, row.index(val)) for index, row in enumerate(self.data) if val in row]
    #res = self.data.index(val)
    return res
  
  def printData(self):
    print(self.dimensions)
    print(self.data)
    
  def closeOutputFile(self):
    self.oFile.close()
    
    
  #Used for preparing lines to be written into the temporary .txt file  
  def setPrintLine(self, line):
    self.templines = self.templines + str(line)
    
  #Used for writing lines into the temporary .txt file    
  def writeToFile(self):
    self.oFile.write(self.templines + '\n')
    self.templines = ''
  
  #This is the Big method which processes the data and saves the resulting paths into a temporary file
  def traverseNodes(self, pivot, nodeVal):
    #lst = []
    val = (pivot[0])
    y = (pivot[1])
    x = (pivot[2])

    #print(val, file = ofile, end=", ")
    printOk = True
  
    #Looking at top node
    if (y - 1) >= 0 and int(self.data[y - 1][x]) < int(val):
      #print('top')
      self.setPrintLine(str(val) + ", ")
      self.traverseNodes([self.data[y - 1][x], (y - 1), x], nodeVal)
      #print('Y', end="\n")
      printOk = False
    
    #Looking at bottom node
    if (y + 1) < self.dimensions[0] and int(self.data[y + 1][x]) < int(val):
      #print('bottom')
      self.setPrintLine(str(val) + ", ")
      self.traverseNodes([self.data[y + 1][x], (y + 1), x], nodeVal)
      #print('Z', end="\n")
      printOk = False
    
    #Looking at right node
    if (x + 1) < self.dimensions[1] and int(self.data[y][x + 1]) < int(val):
      #print('right')
      self.setPrintLine(str(val) + ", ")
      self.traverseNodes([self.data[y][x + 1], y, (x + 1)], nodeVal)
      #print('I', end="\n")
      printOk = False
    
    #Looking at left node
    if (x - 1) >= 0 and int(self.data[y][x - 1]) < int(val):
      #print('left')
      self.setPrintLine(str(val) + ", ")
      self.traverseNodes([self.data[y][x - 1], y, (x - 1)], nodeVal)
      #print('J', end="\n")
      printOk = False
      #print(val, file = ofile, end=" ")
    #if(int(val) > 0):
    if printOk:
      self.setPrintLine(str(val) + ', X')
      #print('X', file = self.oFile, end="\n")
      self.writeToFile()
      #print(nodeVal, file = self.oFile, end=", ")
      printOk = False
  
  
  #Replaces the X character on the txt file to have the node count and difference
  def processOutputFile(self):
    #print("YY")
    fin  = open(self.oFileName, 'r')
    fout = open('temp_python.txt', 'w')
    
    for line in fin:
      #line = line.replace('\n', '')
      arr = line.split(',')
      #fout.write(line.replace('X', str(len(arr) - 1)) + "#" + str(int(arr[0]) - int(arr[-2])))
      count_str = str(len(arr) - 1) + "#" + str(int(arr[0]) - int(arr[-2]))
      fout.write(line.replace('X', count_str))
    fout.close()
    fin.close()
  
  #Processes the txt file to find the best possible path
  def findBiggestDiff(self):
    max_nodes = 0
    diff  = 0
 
    lst = []
    maxlist = []
    
    fin  = open('temp_python.txt', 'r')
    
    for line in fin:
      arr = line.split(',')
      diffs = arr[-1].replace('\n', '').replace(' ', '').split('#')
      
      #print(diffs)
      if int(diffs[0]) >= max_nodes:
        max_nodes = int(diffs[0])
        diff  = int(diffs[1])
        lst.append([diff, max_nodes, line.replace('\n', '')])
        
    diff = 0
    max_nodes = 0
    for val in lst:
      if val[0] > diff and val[1] > max_nodes:
        diff = val[0]
        max_nodes = val[1]
        maxlist = val
          
    print("======================================================")
    print(maxlist)
    print("Best path = ", maxlist[2])
    print("======================================================")
    fin.close()
  
nodes = NodeProcessor('../Data/map.txt', 'output2.txt')
nodes.readInputData()
#nodes.printData()
#print(nodes.getMaxDataValue())
#print(nodes.findValues(1499))
#pivot = nodes.getMaxDataValue()
#val = 1499

ranges = range(1500, -1, -1)
for val in ranges:
  #val = 9
  print(val)
  pivots = nodes.findValues(val)

  for p in pivots:
    p = list(p)
    #print([val, p[0], p[1]])
    nodes.traverseNodes([val, p[0], p[1]], val)
#nodes.traverseNodes(pivot, pivot[0])
nodes.closeOutputFile()
nodes.processOutputFile()
nodes.findBiggestDiff()