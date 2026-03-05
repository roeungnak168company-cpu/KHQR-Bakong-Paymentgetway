 






  // INTERFACE definition (built-in .NET)          
  public interface IDisposable                     
  {                                                  
      void Dispose();  // Contract: "I can be disposed"│ 
  }                                                  
  
                             
  
  // CLASS that IMPLEMENTS the interface           
  // NpgsqlConnection (from PostgreSQL library)    
  public class NpgsqlConnection : IDisposable      
  {                                                  
      public void Dispose()                         
      {                                              
          // Close connection, free resources      
      }                                              
  }                                                  
│  